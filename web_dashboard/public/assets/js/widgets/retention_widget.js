// Retention Widget: show & request retention/export window; admins can modify.
(function(){
  function RetentionWidget(cfg){
    const title = cfg.title || 'Data Retention';
    const root = document.createElement('div'); root.className='widget widget-retention card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">storage</span></div>
      <div class="body" style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <div class="badge" id="ret">retention: -- days</div>
        <div class="badge" id="exp">export window: -- days</div>
        <button class="btn" id="req">Request extension</button>
      </div>
      <div class="meta">Managed by plan/limits</div>
    `;
    const ret=root.querySelector('#ret'), exp=root.querySelector('#exp');
    async function load(){
      try{
        const j = await Auth.api('/api/subscription/limits');
        ret.textContent = 'retention: ' + (j.retention_days ?? '--') + ' days';
        exp.textContent = 'export window: ' + (j.export_window_days ?? '--') + ' days';
      }catch(_){}
    }
    load();
    root.querySelector('#req').onclick = async ()=>{
      await Auth.api('/api/support/ticket', {method:'POST', body:JSON.stringify({type:'retention_extension'})});
      alert('Request sent');
    };
    return { el:root, update(n){ Object.assign(cfg,n||{}); }, destroy(){} };
  }
  WidgetRegistry.register('retention', RetentionWidget);
})();
