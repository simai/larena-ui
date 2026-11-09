#!/usr/bin/env python3
"""Materialize an immutable Larena UI runtime artifact.

The artifact is built from the free SIMAI Framework release (simai/ui, exact Git trees) and the component
packages built over it (components/ of simai/larena-ui, each built by the Framework builder into one archive).
It is written outside any Git repository: no built bytes are committed; the lock in
resources/sf/runtime-lock.json names the release, the packages and their hashes.

Sources are published in lock order: ui, every package of `packages` (each into its own mount), the contract
registry. A lock written before packages were a list (schema_version 5, one top-level `larena` slot) is read as
packages.larena.
"""

from __future__ import annotations

import argparse
import gzip
import hashlib
import io
import json
import re
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


LOCK_SCHEMA = "larena.ui.frontend_runtime_lock"
LOCK_SCHEMA_VERSION = 6
LEGACY_LOCK_SCHEMA_VERSION = 5


def runtime_lock(data: dict) -> dict:
    """The lock in the current shape: a legacy lock's top-level `larena` slot becomes packages.larena."""
    if is_schema(data, LOCK_SCHEMA, LOCK_SCHEMA_VERSION):
        if "larena" in data or not isinstance(data.get("packages"), dict) or not data["packages"]:
            raise RuntimeError("runtime_lock_packages_invalid")
        return data
    if not is_schema(data, LOCK_SCHEMA, LEGACY_LOCK_SCHEMA_VERSION):
        raise RuntimeError("runtime_lock_schema_invalid")
    if "packages" in data or not isinstance(data.get("larena"), dict):
        raise RuntimeError("runtime_lock_legacy_invalid")
    normalized = {key: value for key, value in data.items() if key not in ("larena", "schema", "schema_version")}
    normalized["packages"] = {"larena": {**data["larena"], "licence": {"ref": "Apache-2.0", "tier": "bundled-larena"}}}
    normalized["schema"], normalized["schema_version"] = LOCK_SCHEMA, LOCK_SCHEMA_VERSION
    return normalized


def pair_id(lock: dict) -> str:
    """ui-<12> + "-<name>-<12>" for every package, in lock order."""
    return "ui-" + str(lock["ui"]["commit"])[:12] + "".join(
        f"-{name}-{str(package['commit'])[:12]}" for name, package in lock["packages"].items())


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


# A component set (simai.framework.component-set) carries its passport at the archive root, beside its Smart tree; it
# is published at <mount>/set.json and counted and fingerprinted with the tree. Nothing else may sit outside the tree.
SET_PASSPORT = "set.json"


def package_receipt(source: dict[str, object], archive_path: Path, output: Path) -> dict[str, object]:
    """A component package archive, read as the published tree would be: its files, modes and Git blob ids."""
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
    if not any(path.startswith(f"{tree}/") for path in entries) or any(
            not path.startswith(f"{tree}/") and path != SET_PASSPORT for path in entries):
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


PAIR_RECEIPT_KEYS = ["pair", "sha256", "asset"]
HEX64 = re.compile(r"\A[a-f0-9]{64}\Z")


PAIR_RANGE_KEYS = ["asset", "checked_over", "interface_sha256", "sha256"]
PAIR_RANGE_ASSET = "pair-range.json"
PAIR = re.compile(r"\Aui-[a-f0-9]{12}\Z")


def valid_pair_range(range_entry: object, with_receipt: bool) -> bool:
    """A lock entry's pair range (SIMAI Framework decision 2026-11-09): exactly its four keys, the asset pair-range.json,
    two digests and the pinned pair it was checked over; never beside a pair receipt."""
    return (not with_receipt and isinstance(range_entry, dict) and sorted(range_entry) == PAIR_RANGE_KEYS
            and range_entry["asset"] == PAIR_RANGE_ASSET
            and isinstance(range_entry["sha256"], str) and HEX64.match(range_entry["sha256"]) is not None
            and isinstance(range_entry["interface_sha256"], str) and HEX64.match(range_entry["interface_sha256"]) is not None
            and isinstance(range_entry["checked_over"], str) and PAIR.match(range_entry["checked_over"]) is not None)


def pair_error(package: dict[str, object], framework: str) -> str | None:
    """The same acceptance as ComponentPackageRequirements::pairError: the set was built over the pinned free release,
    or the lock names a pair receipt of the same release for it (Framework decision 2026-11-07), or a pair range of the
    same release checked over it (SIMAI Framework decision 2026-11-09)."""
    receipt = package.get("pair_receipt")
    if "pair_receipt" in package:
        pair = receipt.get("pair") if isinstance(receipt, dict) else None
        if (not isinstance(receipt, dict) or list(receipt) != PAIR_RECEIPT_KEYS
                or not isinstance(pair, str) or not re.fullmatch(r"ui-[a-f0-9]{12}", pair)
                or pair == package.get("requires_framework")
                or not isinstance(receipt["sha256"], str) or not HEX64.match(receipt["sha256"])
                or receipt["asset"] != f"pair-receipt-{pair}.json"):
            return "pair_receipt_invalid"
    range_entry = package.get("pair_range")
    if "pair_range" in package and not valid_pair_range(range_entry, "pair_receipt" in package):
        return "pair_range_invalid"
    if (package.get("requires_framework") == framework
            or (isinstance(receipt, dict) and receipt.get("pair") == framework)
            or (isinstance(range_entry, dict) and range_entry.get("checked_over") == framework)):
        return None
    return "framework_mismatch"


def pair_receipt(name: str, package: dict[str, object], lock: dict[str, object], path: Path | None, output: Path) -> dict[str, object] | None:
    """A set's pair receipt, checked against the lock byte for byte and kept in the artifact beside the set's archive."""
    entry = package.get("pair_receipt")
    if not isinstance(entry, dict):
        return None
    if path is None or not path.is_file() or path.is_symlink():
        raise RuntimeError(f"component_package_pair_receipt_missing:{name}")
    raw = path.read_bytes()
    if sha256(raw) != entry["sha256"]:
        raise RuntimeError(f"component_package_pair_receipt_mismatch:{name}:sha256")
    try:
        receipt = json.loads(raw)
    except ValueError:
        raise RuntimeError(f"component_package_pair_receipt_mismatch:{name}:json")
    def field(*keys: str) -> object:
        value: object = receipt
        for key in keys:
            value = value.get(key) if isinstance(value, dict) else None
        return value
    ui = lock["ui"]
    # The new pair's own ui-source (Framework decision 2026-11-07, set-pair-receipts lines 168-170): the revision the lock
    # pins for the free pair itself, else what a set built over that pair names; with neither the receipt is refused.
    if isinstance(ui.get("source_revision"), str):
        pair_sources = {ui["source_revision"]}
    else:
        pair_sources = {str(other["framework_source"]["revision"]) for other in lock["packages"].values()
                        if other.get("requires_framework") == ui["release"] and isinstance(other.get("framework_source"), dict)}
    expected = [
        ("schema", field("schema") == "simai.framework.set-pair-receipt"),
        ("schema_version", field("schema_version") == 1),
        ("status", field("status") == "passed"),
        ("equivalence", field("checks", "equivalence", "status") == "same-bytes"),
        ("release", field("release") == package["release"]),
        ("set", field("set") == name),
        ("archive_sha256", field("package", "archive_sha256") == package["sha256"]),
        ("manifest_sha256", field("package", "manifest_sha256") == package["manifest_sha256"]),
        ("receipt_sha256", field("package", "receipt_sha256") == package["receipt_sha256"]),
        ("source_revision", field("package", "source_revision") == package["commit"]),
        ("built_over", field("built_over", "pair") == package["requires_framework"]),
        ("pair", field("pair", "id") == ui["release"] and entry["pair"] == ui["release"]),
        ("pair_commit", field("pair", "commit") == ui["commit"]),
        ("pair_source_revision", isinstance(field("pair", "source_revision"), str)
            and re.fullmatch(r"[a-f0-9]{40}", str(field("pair", "source_revision"))) is not None
            and field("pair", "source_revision") in pair_sources),
    ]
    for key, ok in expected:
        if not ok:
            raise RuntimeError(f"component_package_pair_receipt_mismatch:{name}:{key}")
    target = output / str(entry["asset"]).replace("pair-receipt-", f"pair-receipt-{name}-", 1)
    target.write_bytes(raw)
    return {"pair": entry["pair"], "path": f"sources/{target.name}", "sha256": entry["sha256"]}


# The keys of simai.framework.set-pair-range v1 and of its `requires` (ui-builder scripts/set-pair-range.py).
RANGE_KEYS = {"schema", "schema_version", "set", "release", "version", "repository", "package", "built_over",
              "requires", "interface_sha256", "expires", "not_covered", "tooling", "created_on", "claims"}
RANGE_REQUIRES_KEYS = {"core_runtime", "runtime", "relations", "elements", "free_elements", "core_folder", "variables", "js_api"}
# The first ui-builder revision whose set-pair-range.py checks a range over a pair (simai/ui-builder 993e4be).
UI_BUILDER_RANGE_REVISION = "993e4be"
RANGE_REQUIREMENT = re.compile(r"\A(core_runtime|runtime|relations|elements|free_elements|core_folder|variables|js_api):")


def canonical_json(value: object) -> bytes:
    """Keys sorted recursively, separators , and :, UTF-8 with / and non-ASCII unescaped: PHP
    JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE after a recursive ksort."""
    return json.dumps(value, ensure_ascii=False, sort_keys=True, separators=(",", ":")).encode()


def ui_builder_checker(root: Path | None, name: str) -> Path:
    """set-pair-range.py of a simai/ui-builder checkout at 993e4be or later: --ui-builder, else SIMAI_UI_BUILDER_ROOT."""
    if root is None:
        raise RuntimeError(f"runtime_pair_range_checker_missing:{name}:pass --ui-builder or set SIMAI_UI_BUILDER_ROOT to a simai/ui-builder checkout")
    script = root / "scripts" / "set-pair-range.py"
    if not script.is_file() or script.is_symlink():
        raise RuntimeError(f"runtime_pair_range_checker_missing:{name}:{root} has no scripts/set-pair-range.py")
    if (root / ".git").exists():
        known = subprocess.run(["git", "-C", str(root), "merge-base", "--is-ancestor", UI_BUILDER_RANGE_REVISION, "HEAD"],
                               capture_output=True)
        if known.returncode != 0:
            raise RuntimeError(f"runtime_pair_range_checker_too_old:{name}:ui-builder must be at {UI_BUILDER_RANGE_REVISION} or later")
    return script


def check_range_over_pair(name: str, package: dict[str, object], lock: dict[str, object], range_path: Path,
                          document: dict[str, object], ui_repository: Path, ui_builder: Path | None) -> None:
    """Every requirement of the range over the pinned pair, by ui-builder's set-pair-range.py check (exit 0 within,
    1 outside, 2 invalid). The pair is the one the lock pins: ui.release at ui.commit of --ui-repository, shown to
    the checker as that pair's release lock in a scratch view of the same Git objects."""
    script = ui_builder_checker(ui_builder, name)
    ui = lock["ui"]
    git_dir = git_bytes(ui_repository, "rev-parse", "--absolute-git-dir").decode().strip()
    exact_commit(ui_repository, str(ui["commit"]))
    with tempfile.TemporaryDirectory(prefix="pair-range-") as scratch:
        view = Path(scratch) / "ui"
        (view / "contracts" / "releases").mkdir(parents=True)
        pair_lock = {"schema_version": 2, "compatibility_id": ui["release"], "runtime_sources": {"core": {"revision": ui["commit"]}}}
        if isinstance(ui.get("source_revision"), str):
            pair_lock["build_inputs"] = {"source": {"commit": ui["source_revision"]}}
        (view / "contracts" / "releases" / f"{ui['release']}.lock.json").write_text(json.dumps(pair_lock) + "\n")
        # The set's release lock as this lock pins it; the version is the range's own (the runtime lock does not pin it).
        release_lock = {
            "schema": "simai.framework.package-release-lock", "schema_version": 1, "id": package["release"], "package": name,
            "version": document.get("version"),
            "source": {"repository": package["repository"], "revision": package["commit"]},
            "receipt": {"sha256": package["receipt_sha256"]},
            "output": {"manifest_sha256": package["manifest_sha256"], "archive_sha256": package["sha256"]},
            "requires": {"framework": package["requires_framework"]},
        }
        release_lock_path = Path(scratch) / f"{package['release']}.lock.json"
        release_lock_path.write_text(json.dumps(release_lock) + "\n")
        environment = {**os.environ, "GIT_DIR": git_dir}
        environment.pop("GIT_WORK_TREE", None)
        checked = subprocess.run(
            ["python3", "-I", str(script), "check", "--range", str(range_path.resolve()), "--ui", str(view),
             "--pair", str(ui["release"]), "--lock", str(release_lock_path)],
            capture_output=True, text=True, env=environment, cwd=scratch, timeout=600)
    if checked.returncode == 0:
        return
    if checked.returncode == 1:
        try:
            problems = json.loads(checked.stdout)["pairs"][0]["problems"]
        except (ValueError, KeyError, IndexError, TypeError):
            problems = []
        requirement = next((match.group(1) for problem in problems if (match := RANGE_REQUIREMENT.match(str(problem)))), "unknown")
        raise RuntimeError(f"component_package_pair_range_outside:{name}:{requirement}:" + " | ".join(map(str, problems))[:2000])
    raise RuntimeError(f"component_package_pair_range_mismatch:{name}:check:" + (checked.stderr.strip() or checked.stdout.strip())[:2000])


def pair_range(name: str, package: dict[str, object], lock: dict[str, object], path: Path | None, output: Path,
               ui_repository: Path, ui_builder: Path | None) -> dict[str, object] | None:
    """A set's pair range (SIMAI Framework decision 2026-11-09): checked against the lock byte for byte and field by
    field, then over the pinned pair by ui-builder, and kept in the artifact beside the set's archive."""
    entry = package.get("pair_range")
    if not isinstance(entry, dict):
        return None
    if path is None or not path.is_file() or path.is_symlink():
        raise RuntimeError(f"component_package_pair_range_missing:{name}")
    raw = path.read_bytes()
    if sha256(raw) != entry["sha256"]:
        raise RuntimeError(f"component_package_pair_range_mismatch:{name}:sha256")
    try:
        document = json.loads(raw)
    except ValueError:
        raise RuntimeError(f"component_package_pair_range_mismatch:{name}:json")
    if not isinstance(document, dict):
        raise RuntimeError(f"component_package_pair_range_mismatch:{name}:json")
    def field(*keys: str) -> object:
        value: object = document
        for key in keys:
            value = value.get(key) if isinstance(value, dict) else None
        return value
    requires = document.get("requires")
    expected = [
        ("schema", field("schema") == "simai.framework.set-pair-range"),
        ("schema_version", field("schema_version") == 1),
        ("keys", set(document) == RANGE_KEYS and isinstance(requires, dict) and set(requires) == RANGE_REQUIRES_KEYS),
        ("set", field("set") == name),
        ("release", field("release") == package["release"]),
        ("archive_sha256", field("package", "archive_sha256") == package["sha256"]),
        ("manifest_sha256", field("package", "manifest_sha256") == package["manifest_sha256"]),
        ("receipt_sha256", field("package", "receipt_sha256") == package["receipt_sha256"]),
        ("source_revision", field("package", "source_revision") == package["commit"]),
        ("built_over", field("built_over", "pair") == package["requires_framework"]),
        ("interface_sha256", field("interface_sha256") == entry["interface_sha256"]
            and sha256(canonical_json(requires)) == entry["interface_sha256"]),
        ("checked_over", entry["checked_over"] == lock["ui"]["release"]),
    ]
    for key, ok in expected:
        if not ok:
            raise RuntimeError(f"component_package_pair_range_mismatch:{name}:{key}")
    check_range_over_pair(name, package, lock, path, document, ui_repository, ui_builder)
    target = output / f"pair-range-{name}.json"
    target.write_bytes(raw)
    return {"checked_over": entry["checked_over"], "path": f"sources/{target.name}", "sha256": entry["sha256"],
            "interface_sha256": entry["interface_sha256"]}


def pair_range_paths(arguments: argparse.Namespace) -> dict[str, Path]:
    """--pair-range NAME=PATH: the pair range file of a set the lock pins with one."""
    paths: dict[str, Path] = {}
    for value in arguments.pair_range or []:
        name, separator, path = value.partition("=")
        if not separator or not name or not path or name in paths:
            raise RuntimeError(f"runtime_pair_range_option_invalid:{value}")
        paths[name] = Path(path)
    return paths


def pair_receipt_paths(arguments: argparse.Namespace) -> dict[str, Path]:
    """--pair-receipt NAME=PATH: the pair receipt file of a set the lock pins with one."""
    paths: dict[str, Path] = {}
    for value in arguments.pair_receipt or []:
        name, separator, path = value.partition("=")
        if not separator or not name or not path or name in paths:
            raise RuntimeError(f"runtime_pair_receipt_option_invalid:{value}")
        paths[name] = Path(path)
    return paths


def package_archives(arguments: argparse.Namespace, names: list[str]) -> dict[str, Path]:
    """--package-archive NAME=PATH for every package; --larena-package-archive PATH names the one pinned package."""
    archives: dict[str, Path] = {}
    for value in arguments.package_archive or []:
        name, separator, path = value.partition("=")
        if not separator or not name or not path or name in archives:
            raise RuntimeError(f"runtime_package_archive_option_invalid:{value}")
        archives[name] = Path(path)
    if arguments.larena_package_archive is not None:
        if len(names) != 1 or names[0] in archives:
            raise RuntimeError("runtime_package_archive_ambiguous")
        archives[names[0]] = arguments.larena_package_archive
    if sorted(archives) != sorted(names):
        raise RuntimeError("runtime_package_archive_set_mismatch:" + ",".join(sorted(set(names) ^ set(archives))))
    return archives


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--lock", required=True, type=Path)
    parser.add_argument("--ui-repository", required=True, type=Path)
    parser.add_argument("--package-archive", action="append", metavar="NAME=PATH")
    parser.add_argument("--larena-package-archive", type=Path, help="the archive of the one pinned package")
    parser.add_argument("--pair-receipt", action="append", metavar="NAME=PATH", help="a set's pair receipt (pair_receipt in the lock)")
    parser.add_argument("--pair-range", action="append", metavar="NAME=PATH", help="a set's pair range (pair_range in the lock)")
    parser.add_argument("--ui-builder", type=Path, default=Path(os.environ["SIMAI_UI_BUILDER_ROOT"]) if os.environ.get("SIMAI_UI_BUILDER_ROOT") else None,
                        help="a simai/ui-builder checkout at 993e4be or later, which checks a pair range over the pair (default: SIMAI_UI_BUILDER_ROOT)")
    parser.add_argument("--output-root", required=True, type=Path)
    arguments = parser.parse_args()

    lock = runtime_lock(json.loads(arguments.lock.read_text()))
    if lock.get("publication_profile") != "exact-git-tree-v2":
        raise RuntimeError("runtime_publication_profile_invalid")
    bundle_id = str(lock["bundle_id"])
    if lock["pair_id"] != pair_id(lock):
        raise RuntimeError("runtime_pair_identity_mismatch")
    expected_bundle = f"{lock['pair_id']}-registry-{lock['framework_registry']['file_sha256'][:8]}-exact-git-tree-v2"
    if bundle_id != expected_bundle:
        raise RuntimeError("runtime_bundle_identity_mismatch")
    for name, package in lock["packages"].items():
        pair = pair_error(package, lock["ui"]["release"])
        if pair in ("pair_receipt_invalid", "pair_range_invalid"):
            raise RuntimeError(f"component_package_{pair}:{name}")
        if pair is not None:
            raise RuntimeError(f"runtime_package_framework_mismatch:{name}")
        if package["mount"] != name:
            raise RuntimeError(f"runtime_package_mount_invalid:{name}")
    archives = package_archives(arguments, list(lock["packages"]))
    destination = arguments.output_root / bundle_id
    if destination.exists() or destination.is_symlink():
        raise RuntimeError("runtime_artifact_already_exists")

    arguments.output_root.mkdir(parents=True, exist_ok=True)
    stage = Path(tempfile.mkdtemp(prefix=f".{bundle_id}.", dir=arguments.output_root))
    try:
        sources = stage / "sources"
        sources.mkdir()
        receipt_paths = pair_receipt_paths(arguments)
        unknown = sorted(set(receipt_paths) - {name for name, package in lock["packages"].items() if "pair_receipt" in package})
        if unknown:
            raise RuntimeError("runtime_pair_receipt_unexpected:" + ",".join(unknown))
        range_paths = pair_range_paths(arguments)
        unknown = sorted(set(range_paths) - {name for name, package in lock["packages"].items() if "pair_range" in package})
        if unknown:
            raise RuntimeError("runtime_pair_range_unexpected:" + ",".join(unknown))
        packaged = []
        for name, package in lock["packages"].items():
            entry = package_receipt(package, archives[name], sources)
            checked = pair_receipt(name, package, lock, receipt_paths.get(name), sources)
            if checked is not None:
                entry["pair_receipt"] = checked
            ranged = pair_range(name, package, lock, range_paths.get(name), sources, arguments.ui_repository, arguments.ui_builder)
            if ranged is not None:
                entry["pair_range"] = ranged
            packaged.append(entry)
        receipts = [
            source_receipt(lock["ui"], arguments.ui_repository, "ui.tar.gz", sources),
            *packaged,
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
