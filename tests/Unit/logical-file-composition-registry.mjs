import assert from 'node:assert/strict';
import { createRegistry } from '../../resources/composition/larena-registry.mjs';
let registered;
const registry = createRegistry({createRegistry(types, renderers) {
  if (!types) return {types:new Map([['builtin',{type:'layout.section'}]])};
  registered={types,renderers}; return registered;
}});
assert.equal(registry.types.at(-1).type,'larena.logical-file-image');
const image = registry.renderers['larena.logical-file-image'];
const html=image({node:{id:'instance-1',props:{public_id:'file-id',extension:'png',alt:'" <tag> &'}}});
assert.match(html,/src="\/media\/file-id\/image.png"/);
assert.match(html,/alt="&quot; &lt;tag&gt; &amp;"/);
for(const public_id of ['../private','javascript:alert(1)','file/id']) {
  assert.throws(()=>image({node:{id:'x',props:{public_id,extension:'png',alt:''}}}),/metadata_invalid/);
}
assert.throws(()=>image({node:{id:'x',props:{public_id:'valid',extension:'svg',alt:''}}}),/metadata_invalid/);
assert.match(registry.renderers['layout.section']({node:{id:'x',presentation:{preset:'contrast'}},slots:{default:'<p>trusted HTML</p>'}}),/preset="contrast"/);
console.log('Logical file composition registry tests passed.');
