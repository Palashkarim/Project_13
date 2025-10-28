// Export Request: user picks a time window + format; creates an export job; polls status.
(function(){
  function ExportRequest(cfg){
    const title = cfg.title || 'Export Data';
    const root = document.createElement('div'); root.className='widget widget-export card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">download</span></div>
      <div class="body" style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <div>
          <label>From</label><input id="from" class="input" type="datetime-local">
          <label style="margin-top:8px;display:block">To</label><input id="to" class="input" type="datetime-local">
          <label style="margin-top:8px;display:block">Format</label>
          <select id="fmt"><option value="csv">CSV</option><option value="json">JSON</option></select>
        </div>
        <div>
          <button class="btn" id="create" style="margin-top:24px">Create Export</button>
          <div class="meta" id="status">Idle</div>
          <div id="link" class="meta"></div>
        </div>
      </div>
    `;
    const elFrom=root.querySelector('#from'), elTo=root.querySelector('#to'), elFmt=root.querySelector('#fmt'), status=root.querySelector('#status'), link=root.querySelector('#link');
    let pollTimer=null;

    function isoLocal(dt){ const z=dt.getTimezoneOffset()*60000; return new Date(dt - z).toISOString().slice(0,16); }
    elFrom.value = isoLocal(Date.now()-3600*1000*24); // yesterday
    elTo.value   = isoLocal(new Date());

    async function poll(id){
      clearInterval(pollTimer);
      pollTimer = setInterval(async ()=>{
        const j = await Auth.api(`/api/exports/${id}`);
        status.textContent = `Status: ${j.status}`;
        if (j.status === 'done' && j.file_path){
          clearInterval(pollTimer);
          link.innerHTML = `<a href="${j.file_path}" target="_blank">Download</a>`;
        }
        if (j.status === 'error') clearInterval(pollTimer);
      }, 3000);
    }

    root.querySelector('#create').onclick = async ()=>{
      status.textContent='Creating...'; link.textContent='';
      const body = { format: elFmt.value, from_ts: new Date(elFrom.value).toISOString(), to_ts: new Date(elTo.value).toISOString() };
      const j = await Auth.api('/api/exports', {method:'POST', body:JSON.stringify(body)});
      if (j && j.id){ status.textContent='Queued'; poll(j.id); } else { status.textContent='Failed'; }
    };

    return { el:root, update(n){ Object.assign(cfg,n||{}); }, destroy(){ clearInterval(pollTimer); } };
  }
  WidgetRegistry.register('export_request', ExportRequest);
})();
