class Port extends HTMLElement {
 setRows(rows){this.rows=rows;} setState(){} setFilterFields(){} setTemplates(){} setTableSettings(){}
}
customElements.define('sf-table',class extends Port{});customElements.define('sf-pagination',class extends Port{});customElements.define('sf-admin-menu',class extends Port{});
const pending=[];window.fetch=(url,options)=>new Promise(resolve=>pending.push({options,resolve:p=>resolve({ok:true,json:async()=>p})}));
const pause=()=>new Promise(r=>setTimeout(r,0));
const payload=id=>({query:{page:1},rows:[{id}],pagination:{page:1,total:1,pageSize:10}});
const search=(table,text)=>table.dispatchEvent(new CustomEvent('onSearchEnd',{detail:text}));
const make=()=>{
 const host=document.createElement('section');host.dataset.larenaDataviewWorkbench='';
 host.innerHTML='<sf-table></sf-table><sf-pagination></sf-pagination><output data-larena-dataview-status></output><script type="application/json" data-larena-dataview-state>{"query_endpoint":"/query","profiles":{},"query":{"page":1}}</script>';
 return host;
};
document.getElementById('run').onclick=async()=>{
 const report=[];const check=(ok,name)=>{if(!ok)throw new Error(name);report.push('PASS '+name);};
 try{
 const a=make(),b=make();document.getElementById('hosts').append(a,b);await pause();await pause();
 const ta=a.querySelector('sf-table'),tb=b.querySelector('sf-table');
 search(ta,'old');search(ta,'new');search(tb,'other');check(pending.length===3,'single mount and independent subscriptions');
 pending[1].resolve(payload('new'));pending[2].resolve(payload('other'));await pause();pending[0].resolve(payload('old'));await pause();
 check(ta.rows[0].id==='new'&&tb.rows[0].id==='other','latest result and independent views');
 search(ta,'late');a.remove();await pause();check(pending[3].options.signal.aborted,'unmount aborts request');
 search(ta,'detached');check(pending.length===4,'unmount removes subscriptions');pending[3].resolve(payload('late'));await pause();check(ta.rows[0].id==='new','late result cannot write');
 document.getElementById('hosts').append(a);await pause();search(ta,'remount');check(pending.length===5,'remount binds once');
 pending[4].resolve(payload('remount'));await pause();check(ta.rows[0].id==='remount','remounted result applies');
 a.remove();b.remove();document.getElementById('result').textContent=report.join('\n')+'\nALL CHECKS PASSED';
 }catch(e){document.getElementById('result').textContent=report.join('\n')+'\nFAIL '+e.message;}
};
