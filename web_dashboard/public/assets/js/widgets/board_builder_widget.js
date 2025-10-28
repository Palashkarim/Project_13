// Board Builder: drag/drop widget tiles to re-order inside a board, save layout via API.
(function(){
  function BoardBuilder(cfg){
    // cfg.board_id, cfg.widgets: [{id,type,title,cfg,sort_order}]
    const title = cfg.title || 'Board Builder';
    const items = Array.isArray(cfg.widgets) ? cfg.widgets.slice().sort((a,b)=> (a.sort_order||0)-(b.sort_order||0)) : [];

    const root = document.createElement('div'); root.className='widget widget-boardbuilder card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">drag & drop</span></div>
      <div class="body" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px" id="grid"></div>
      <div class="meta"><button class="btn" id="save">Save order</button></div>
    `;
    const grid = root.querySelector('#grid');

    items.forEach(w=>{
      const d = document.createElement('div');
      d.className = 'card';
      d.draggable = true;
      d.dataset.id = w.id;
      d.style.cursor = 'move';
      d.style.textAlign = 'center';
      d.style.padding = '10px';
      d.innerHTML = `<div style="font-weight:600">${w.title||w.type}</div><div class="meta">#${w.id}</div>`;
      grid.appendChild(d);
    });

    let dragEl=null;
    grid.addEventListener('dragstart', e=>{ const t=e.target.closest('.card'); if(!t) return; dragEl=t; e.dataTransfer.effectAllowed='move'; });
    grid.addEventListener('dragover', e=>{ e.preventDefault(); const t=e.target.closest('.card'); if(!t||t===dragEl) return; const rect=t.getBoundingClientRect(); const after = (e.clientY - rect.top) / rect.height > 0.5; grid.insertBefore(dragEl, after?t.nextSibling:t); });
    grid.addEventListener('drop', e=>{ e.preventDefault(); dragEl=null; });

    root.querySelector('#save').onclick = async ()=>{
      const order = Array.from(grid.querySelectorAll('.card')).map((el,i)=>({id: Number(el.dataset.id), sort_order:i}));
      await Auth.api(`/api/boards/${cfg.board_id}/order`, {method:'POST', body:JSON.stringify({order})});
      alert('Saved');
    };

    return { el:root, update(n){ Object.assign(cfg,n||{}); }, destroy(){} };
  }
  WidgetRegistry.register('board_builder', BoardBuilder);
})();
