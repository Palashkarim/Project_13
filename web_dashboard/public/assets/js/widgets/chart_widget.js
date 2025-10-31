// Chart: lightweight time-series viewer using <canvas>. No external libs.
// It buffers the last N points from a tele topic and draws a line.
(function(){
  function ChartWidget(cfg){
    const title = cfg.title || 'Chart';
    const metricKey = cfg.metric || 'value';
    const maxPoints = cfg.max_points || 200;

    const root = document.createElement('div'); root.className='widget widget-chart card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">${metricKey}</span></div>
      <div class="body" style="width:100%;height:160px"><canvas width="600" height="140"></canvas></div>
      <div class="meta">${cfg.topic_tele || ''}</div>
    `;
    const canvas = root.querySelector('canvas');
    const ctx = canvas.getContext('2d');
    const data = []; // {x:ts, y:value}
    let unsub = null;

    function draw(){
      const w = canvas.width, h = canvas.height;
      ctx.clearRect(0,0,w,h);
      if (data.length < 2) return;
      const minX = data[0].x, maxX = data[data.length-1].x;
      const ys = data.map(d=>d.y);
      const minY = Math.min(...ys), maxY = Math.max(...ys);
      const rngY = (maxY - minY) || 1;
      ctx.beginPath();
      data.forEach((d,i)=>{
        const x = ( (d.x - minX) / (maxX - minX || 1) ) * (w-10) + 5;
        const y = h - (((d.y - minY)/rngY) * (h-10) + 5);
        if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y);
      });
      ctx.lineWidth = 2;
      ctx.strokeStyle = '#4f8cff';
      ctx.stroke();
    }

    if (cfg.topic_tele){
      unsub = MQTTClient.subscribe(cfg.topic_tele, (_t, p)=>{
        try{
          const j = JSON.parse(p);
          const v = j[metricKey] ?? j.value;
          if (typeof v === 'number'){
            data.push({x: Date.now(), y: v});
            while (data.length > maxPoints) data.shift();
            draw();
          }
        }catch(_){}
      });
    }

    return {
      el: root,
      update(n){ Object.assign(cfg, n||{}); },
      destroy(){ if (unsub) unsub(); }
    };
  }
  WidgetRegistry.register('chart', ChartWidget);
})();

