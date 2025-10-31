// Widget Matrix: admins/techs check which widget types a user may access.
// Saves to /api/widgets/allowlist {user_id, allowed: [keys...]}
(function(){
  function WidgetMatrix(cfg){
    const title = cfg.title || 'Widget Access';
    const all = cfg.all_types || Object.keys(WidgetRegistry._types || {});
    const allowed = new Set(cfg.allowed || []);
    const root = document.createElement('div'); root.className='widget widget-matrix card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">permissions</span></div>
      <div class="body" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:6px" id="grid"></div>
      <div class="meta"><button class="btn" id="save">Save</button></div>
    `;
    const grid=root.querySelector('#grid');
    all.forEach(k=>{
      const id='w_'+k;
      const row = document.createElement('label');
      row.style.display='flex'; row.style.alignItems='center'; row.style.gap='6px';
      row.innerHTML = `<input type="checkbox" id="${id}" ${allowed.has(k)?'checked':''}/> <span>${k}</span>`;
      grid.appendChild(row);
    });
    root.querySelector('#save').onclick = async ()=>{
      const sel = Array.from(grid.querySelectorAll('input[type=checkbox]')).filter(i=>i.checked).map(i=>i.id.slice(2));
      await Auth.api('/api/widgets/allowlist', {method:'POST', body:JSON.stringify({user_id: cfg.user_id, allowed: sel})});
      alert('Saved');
    };
    return { el:root, update(n){ Object.assign(cfg,n||{}); }, destroy(){} };
  }
  WidgetRegistry.register('widget_matrix', WidgetMatrix);
})();
