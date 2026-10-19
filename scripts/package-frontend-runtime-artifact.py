#!/usr/bin/env python3
"""Materialize an immutable Larena UI runtime artifact.

The artifact is built from the free SIMAI Framework release (simai/ui, exact Git trees) and the Larena
components package built over it (components/ of simai/larena-ui, built by the Framework builder into one
archive). It is written outside any Git repository: no built bytes are committed; the lock in
resources/sf/runtime-lock.json names the release, the package and their hashes.
"""

from __future__ import annotations

import argparse
import gzip
import hashlib
import io
import json
import os
from pathlib import Path
import shutil
import subprocess
import tarfile
import tempfile


def is_schema(data: dict, name: str, version: int) -> bool:
    """A format is named once and carries its version as a field; data written before the move names it in the label."""
    schema, declared = data.get("schema"), data.get("schema_version")
    return (schema == name and declared == version) or (schema == f"{name}.v{version}" and declared in (None, version))


def git_bytes(repository: Path, *arguments: str) -> bytes:
    return subprocess.check_output(["git", "-C", str(repository), *arguments])


def sha256(payload: bytes) -> str:
    return hashlib.sha256(payload).hexdigest()


def exact_commit(repository: Path, revision: str) -> str:
    commit = git_bytes(repository, "rev-parse", f"{revision}^{{commit}}").decode().strip()
    if commit != revision:
        raise RuntimeError(f"runtime_commit_not_exact:{revision}")
    return commit


def tree_receipt(repository: Path, commit: str, tree: str) -> tuple[int, str]:
    listing = git_bytes(repository, "ls-tree", "-r", commit, tree).decode().splitlines()
    fingerprint = hashlib.sha256()
    count = 0
    for line in listing:
        metadata, path = line.split("\t", 1)
        mode, kind, object_id = metadata.split(" ")
        if kind != "blob" or mode not in {"100644", "100755"}:
            raise RuntimeError(f"runtime_tree_entry_unsupported:{path}")
        fingerprint.update(f"{mode} {object_id}\t{path}\0".encode())
        count += 1
    return count, fingerprint.hexdigest()


def deterministic_gzip(payload: bytes) -> bytes:
    output = io.BytesIO()
    with gzip.GzipFile(filename="", mode="wb", fileobj=output, compresslevel=9, mtime=0) as archive:
        archive.write(payload)
    return output.getvalue()


def source_receipt(source: dict[str, object], repository: Path, archive_name: str, output: Path) -> dict[str, object]:
    commit = str(source["commit"])
    tree = str(source["tree"])
    exact_commit(repository, commit)
    raw_archive = git_bytes(repository, "archive", "--format=tar", commit, tree)
    actual_archive_sha = sha256(raw_archive)
    if actual_archive_sha != str(source["sha256"]):
        raise RuntimeError(f"runtime_archive_sha256_mismatch:{source['mount']}")
    file_count, fingerprint = tree_receipt(repository, commit, tree)
    if file_count != int(source["files"]):
        raise RuntimeError(f"runtime_file_count_mismatch:{source['mount']}")
    compressed = deterministic_gzip(raw_archive)
    (output / archive_name).write_bytes(compressed)
    return {
        "commit": commit,
        "tree": tree,
        "mount": source["mount"],
        "archive_sha256": actual_archive_sha,
        "files": file_count,
        "archive": f"sources/{archive_name}",
        "compressed_sha256": sha256(compressed),
        "tree_fingerprint_sha256": fingerprint,
        "object_format": git_bytes(repository, "rev-parse", "--show-object-format").decode().strip(),
        "file_count": file_count,
    }


def git_blob_sha1(payload: bytes) -> str:
    return hashlib.sha1(b"blob %d\0" % len(payload) + payload).hexdigest()


def package_receipt(source: dict[str, object], archive_path: Path, output: Path) -> dict[str, object]:
    """The Larena package archive, read as the published tree would be: its files, modes and Git blob ids."""
    raw_archive = archive_path.read_bytes()
    actual_archive_sha = sha256(raw_archive)
    if actual_archive_sha != str(source["sha256"]):
        raise RuntimeError(f"runtime_archive_sha256_mismatch:{source['mount']}")
    entries: dict[str, tuple[str, str]] = {}
    with tarfile.open(fileobj=io.BytesIO(raw_archive), mode="r:") as archive:
        for member in archive.getmembers():
            name = member.name
            if name.startswith("/") or ".." in name.split("/") or "\\" in name:
                raise RuntimeError(f"runtime_package_entry_unsafe:{name}")
            if member.isdir():
                continue
            if not member.isfile():
                raise RuntimeError(f"runtime_package_entry_unsupported:{name}")
            payload = archive.extractfile(member).read()
            mode = "100755" if member.mode & 0o111 else "100644"
            entries[name] = (mode, git_blob_sha1(payload))
    tree = str(source["tree"])
    if not entries or any(not path.startswith(f"{tree}/") for path in entries):
        raise RuntimeError(f"runtime_package_tree_invalid:{source['mount']}")
    if len(entries) != int(source["files"]):
        raise RuntimeError(f"runtime_file_count_mismatch:{source['mount']}")
    fingerprint = hashlib.sha256()
    for path in sorted(entries):
        mode, object_id = entries[path]
        fingerprint.update(f"{mode} {object_id}\t{path}\0".encode())
    compressed = deterministic_gzip(raw_archive)
    archive_name = f"{source['mount']}.tar.gz"
    (output / archive_name).write_bytes(compressed)
    return {
        "commit": str(source["commit"]),
        "tree": tree,
        "mount": source["mount"],
        "archive_sha256": actual_archive_sha,
        "files": len(entries),
        "archive": f"sources/{archive_name}",
        "compressed_sha256": sha256(compressed),
        "tree_fingerprint_sha256": fingerprint.hexdigest(),
        "object_format": "sha1",
        "file_count": len(entries),
    }


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--lock", required=True, type=Path)
    parser.add_argument("--ui-repository", required=True, type=Path)
    parser.add_argument("--larena-package-archive", required=True, type=Path)
    parser.add_argument("--output-root", required=True, type=Path)
    arguments = parser.parse_args()

    lock = json.loads(arguments.lock.read_text())
    if not is_schema(lock, "larena.ui.frontend_runtime_lock", 5):
        raise RuntimeError("runtime_lock_schema_invalid")
    if lock.get("publication_profile") != "exact-git-tree-v2":
        raise RuntimeError("runtime_publication_profile_invalid")
    bundle_id = str(lock["bundle_id"])
    expected_bundle = f"{lock['pair_id']}-registry-{lock['framework_registry']['file_sha256'][:8]}-exact-git-tree-v2"
    if bundle_id != expected_bundle:
        raise RuntimeError("runtime_bundle_identity_mismatch")
    if lock["larena"]["requires_framework"] != lock["ui"]["release"]:
        raise RuntimeError("runtime_larena_framework_mismatch")
    destination = arguments.output_root / bundle_id
    if destination.exists() or destination.is_symlink():
        raise RuntimeError("runtime_artifact_already_exists")

    arguments.output_root.mkdir(parents=True, exist_ok=True)
    stage = Path(tempfile.mkdtemp(prefix=f".{bundle_id}.", dir=arguments.output_root))
    try:
        sources = stage / "sources"
        sources.mkdir()
        receipts = [
            source_receipt(lock["ui"], arguments.ui_repository, "ui.tar.gz", sources),
            package_receipt(lock["larena"], arguments.larena_package_archive, sources),
            source_receipt(lock["framework_registry"]["source"], arguments.ui_repository, "contract.tar.gz", sources),
        ]
        manifest = {
            "schema": "larena.ui.frontend_runtime_artifact",
            "publication_profile": "exact-git-tree-v2",
            "bundle_id": bundle_id,
            "sources": receipts,
        }
        (stage / "manifest.json").write_text(json.dumps(manifest, indent=4, ensure_ascii=False) + "\n")
        os.replace(stage, destination)
    except BaseException:
        shutil.rmtree(stage, ignore_errors=True)
        raise

    print(json.dumps({
        "schema": "larena.ui.frontend_runtime_artifact_materialization.v1",
        "status": "passed",
        "bundle_id": bundle_id,
        "artifact": str(destination),
        "manifest_sha256": sha256((destination / "manifest.json").read_bytes()),
    }, indent=2))


if __name__ == "__main__":
    main()
