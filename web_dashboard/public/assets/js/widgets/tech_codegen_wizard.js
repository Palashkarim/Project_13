// Technician Codegen Wizard: select hardware + widgets -> call API to generate firmware ZIP.
(function(){
  function TechWizard(cfg){
    const title = cfg.title || 'Tech Codegen';
    const root = document.createElement('div'); root.className='widget widget-techgen card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">firmware</span></div>
      <div class="body" style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <div>
          <label>Hardware</label>
          <select id="hw"><option value="esp32">ESP32</option><option value="esp8266">ESP8266</option><option value="c_freertos">C/FreeRTOS</option></select>
          <label style="display:block;margin-top:8px">Device ID</label>
          <input id="devid" class="input" placeholder="device_001"/>
          <label style="display:block;margin-top:8px">WiFi SSID</label>
          <input id="ssid" class="input" placeholder="MyWiFi"/>
          <label style="display:block;margin-top:8px">WiFi Pass</label>
          <input id="pass" class="input" placeholder="********"/>
        </div>
        <div>
          <label>Widgets</label>
          <div id="wlist" style="display:grid;grid-template-columns:repeat(2,1fr);gap:6px"></div>
          <button class="btn" id="build" style="margin-top:8px">Generate Firmware</button>
          <div class="meta" id="res">Ready</div>
        </div>
      </div>
    `;

    const hw=root.querySelector('#hw'), devid=root.querySelector('#devid'), ssid=root.querySelector('#ssid'), pass=root.querySelector('#pass');
    const wlist=root.querySelector('#wlist'), res=root.querySelector('#res');
    // Load allowed widget keys for this user (or pass via cfg)
    const all = cfg.all_types || Object.keys(WidgetRegistry._types||{});
    all.forEach(k=>{
      const id='wg_'+k;
      const row=document.createElement('label');
      row.style.display='flex'; row.style.alignItems='center'; row.style.gap='6px';
      row.innerHTML=`<input type="checkbox" id="${id}"/> <span>${k}</span>`;
      wlist.appendChild(row);
    });

    root.querySelector('#build').onclick = async ()=>{
      const selected = Array.from(wlist.querySelectorAll('input[type=checkbox]')).filter(i=>i.checked).map(i=>i.id.slice(3));
      const payload = {
        user_id: cfg.user_id,
        hardware: hw.value,
        device_id: devid.value.trim(),
        wifi_ssid: ssid.value,
        wifi_pass: pass.value,
        widgets: selected
      };
      res.textContent = 'Building...';
      const j = await Auth.api('/api/technician/build', {method:'POST', body:JSON.stringify(payload)});
      if (j && j.zip_url){ res.innerHTML = `Done: <a href="${j.zip_url}" target="_blank">Download ZIP</a>`; }
      else { res.textContent = 'Build failed'; }
    };

    return { el:root, update(n){ Object.assign(cfg,n||{}); }, destroy(){} };
  }
  WidgetRegistry.register('tech_codegen_wizard', TechWizard);
})();
