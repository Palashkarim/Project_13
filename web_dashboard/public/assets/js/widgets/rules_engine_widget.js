// Rules Engine: client-side preview & save to API. Backend evaluates rules server-side.
// cfg.rules: array of {if:{metric,op,value}, then:{topic_cmd,payload}}
(function(){
  function RulesWidget(cfg){
    const title = cfg.title || 'Rules';
    const rules = Array.isArray(cfg.rules)? cfg.rules : [];
    const root = document.createElement('div'); root.className='widget widget-rules card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">automation</span></div>
      <div class="body" style="flex-direction:column;gap:8px">
        <div id="list" class="meta"></div>
        <button class="btn" id="save">Save</button>
      </div>
      <div class="meta">Rules evaluated on server</div>
    `;
    const list = root.querySelector('#list');
    function render(){
      list.innerHTML = rules.map((r,i)=>`#${i+1} IF ${r.if.metric} ${r.if.op} ${r.if.value} → ${r.then.topic_cmd} :: ${r.then.payload}`).join('<br>');
    }
    render();
    root.querySelector('#save').onclick = async ()=>{
      const res = await Auth.api('/api/rules/save', {method:'POST', body:JSON.stringify({rules})});
      console.log('rules saved', res);
    };
    return { el:root, update(n){ Object.assign(cfg,n||{}); }, destroy(){} };
  }
  WidgetRegistry.register('rules_engine', RulesWidget);
})();

