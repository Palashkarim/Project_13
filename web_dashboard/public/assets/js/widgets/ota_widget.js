// OTA Widget: upload firmware.bin and publish "ota:url:<http>" or trigger API call.
(function(){
  function OTAWidget(cfg){
    const title = cfg.title || 'OTA Update';
    const root = document.createElement('div'); root.className='widget widget-ota card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">${cfg.device_id||''}</span></div>
      <div class="body" style="flex-direction:column;gap:8px">
        <input type="file" id="file" accept=".bin" />
        <div class="row">
          <button class="btn" id="upload">Upload</button>
          <button class="btn" id="trigger">Trigger OTA</button>
        </div>
        <div class="meta" id="status">Select a firmware .bin</div>
      </div>
    `;
    const file = root.querySelector('#file');
    const status = root.querySelector('#status');

    async function uploadBin(){
      if (!file.files[0]) return;
      const fd = new FormData(); fd.append('firmware', file.files[0]);
      const res = await fetch('/api/ota/upload', { method:'POST', body:fd, headers: Auth.token()?{Authorization:'Bearer '+Auth.token()}:{} });
      if(!res.ok){ status.textContent='Upload failed'; return; }
      const j = await res.json();
      status.textContent = 'Uploaded: ' + j.url;
      cfg.url = j.url;
    }

    root.querySelector('#upload').onclick = uploadBin;
    root.querySelector('#trigger').onclick = ()=>{
      if (!cfg.topic_cmd || !cfg.url){ status.textContent='Upload first'; return; }
      MQTTClient.publish(cfg.topic_cmd, 'ota:url:'+cfg.url);
      status.textContent='OTA triggered';
    };

    return { el: root, update(n){ Object.assign(cfg,n||{}); }, destroy(){} };
  }
  WidgetRegistry.register('ota', OTAWidget);
})();
