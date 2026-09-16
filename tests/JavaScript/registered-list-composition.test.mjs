import test from 'node:test';
import assert from 'node:assert/strict';
import { pathToFileURL } from 'node:url';
import { readFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { createRegistry } from '../../resources/composition/larena-registry.mjs';

const root = process.env.SIMAI_UI_ROOT;
if (!root) throw new Error('Exact SIMAI_UI_ROOT is required; unconfigured conformance cannot pass.');
const framework = await import(pathToFileURL(`${root}/distr/core/js/composition/index.mjs`));
const registry = createRegistry(framework);
const leaf = { id: 'list-auth', type: 'larena.registered-list', props: { source_key: 'auth.users', column_preset: 'users', title: 'Пользователи' } };
const document = { schema: 'simai.composition.document.v1', id: 'page.lists', profile: 'ui-layout', locale: 'ru', root: { id: 'page', type: 'layout.page', slots: { default: [leaf] } } };

test('trusted product manifest uses existing Framework grammar and publishes only a host', async () => {
  assert.equal(framework.validate(document, registry).valid, true);
  const result = await framework.render(document, { registry });
  assert.deepEqual(result.diagnostics, []);
  assert.match(result.html, /data-larena-request-list="list-auth"/);
  assert.match(result.html, /data-source-key="auth.users"/);
  assert.doesNotMatch(result.html, /password|record_id|email/);
});

test('unregistered request data, endpoints, scope and actor props are rejected', () => {
  for (const [key, value] of [['rows', []], ['endpoint', '/arbitrary'], ['scope', 'scope:foreign'], ['actor', 'user:admin_identity:1']]) {
    const invalid = structuredClone(document);
    invalid.root.slots.default[0].props[key] = value;
    assert.equal(framework.validate(invalid, registry).valid, false, key);
  }
});

test('leaf nesting, unknown views and malformed source registration keys fail', () => {
  const cases = [
    { slots: { default: [{ ...leaf, id: 'child' }] } },
    { presentation: { view: 'unknown' } },
    { props: { ...leaf.props, source_key: 'https://example.com/data' } },
  ];
  for (const patch of cases) {
    const invalid = structuredClone(document);
    Object.assign(invalid.root.slots.default[0], patch);
    assert.equal(framework.validate(invalid, registry).valid, false);
  }
});

test('two sources preserve their structural identity without embedding query data', async () => {
  const pair = structuredClone(document);
  pair.root.slots.default.push({ id: 'list-storage', type: 'larena.registered-list', props: { source_key: 'storage.probe', column_preset: 'records', title: 'Records' } });
  const result = await framework.render(pair, { registry });
  assert.deepEqual(result.diagnostics, []);
  assert.match(result.html, /data-larena-request-list="list-auth"/);
  assert.match(result.html, /data-larena-request-list="list-storage"/);
  assert.doesNotMatch(result.html, /dataset|snapshot_id|record_id/);
});


test('Recipe compiler preserves registered static host props and derives stable node identities', async () => {
  const lock = JSON.parse(readFileSync(`${root}/distr/core/contracts/composition-recipe-v1/contract.lock.json`, 'utf8'));
  const digest = 'sha256:' + createHash('sha256').update(readFileSync(new URL('../../resources/composition/registered-list.type-manifest.json', import.meta.url))).digest('hex');
  const props = Object.fromEntries(Object.entries(leaf.props).map(([key, value]) => [key, { literal: value }]));
  const recipe = { schema: 'simai.composition.recipe.v1', id: 'page.list-recipe', profile: 'ui-layout', root: { id: 'root', node: { type: 'layout.page', slots: { default: [{ id: 'auth', node: { type: leaf.type, props } }] } } } };
  const options = { registry, trustedContext: { scope: 'scope:synthetic' }, executionContract: { contractDigest: lock.contractDigest, registryDigest: digest, rendererDigest: digest } };
  const result = await framework.resolveRecipe(recipe, options);
  assert.deepEqual(result.diagnostics, []);
  assert.equal(result.document.schema, 'simai.composition.document.v1');
  const node = result.document.root.slots.default[0];
  assert.equal(node.type, 'larena.registered-list');
  assert.deepEqual(node.props, leaf.props);
  assert.match(node.id, /^n-[a-f0-9]{64}$/);
  const repeated = await framework.resolveRecipe(recipe, options);
  assert.deepEqual(repeated.document, result.document);
});
