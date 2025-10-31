// Map Tracker: very lightweight map with Maplib (placeholder). Subscribes to GPS tele.
(function(){
  function MapTrackerWidget(cfg){
    const title = cfg.title || 'Map';
    const root = document.createElement('div'); root.className='widget widget-map card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">${cfg.asset_name||'asset'}</span></div>
      <div class="body" style="width:100%;height:220px"><div id="map" style="width:100%;height:200px;background:#0e1117;border-radius:10px;display:grid;place-items:center;color:var(--muted)">Map placeholder</div></div>
      <div class="meta">${cfg.topic_tele||''}</div>
    `;
    const mapEl = root.querySelector('#map');
    let last = null, unsub=null;

    function renderMarker(lat, lon){
      mapEl.textContent = `Lat: ${lat.toFixed(5)}, Lon: ${lon.toFixed(5)}`;
    }

    if (cfg.topic_tele){
      unsub = MQTTClient.subscribe(cfg.topic_tele, (_t,p)=>{
        try{
          const j = JSON.parse(p);
          const lat = parseFloat(j.lat ?? j.latitude);
          const lon = parseFloat(j.lon ?? j.longitude);
          if (!isNaN(lat) && !isNaN(lon)){
            last = {lat, lon};
            renderMarker(lat, lon);
          }
        }catch(_){}
      });
    }
    if (last) renderMarker(last.lat,last.lon);
    return { el: root, update(n){ Object.assign(cfg,n||{}); }, destroy(){ if (unsub) unsub(); } };
  }
  WidgetRegistry.register('map_tracker', MapTrackerWidget);
})();
