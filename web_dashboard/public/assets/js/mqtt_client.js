// MQTT client wrapper using MQTT.js (served as vendors/mqtt.min.js)
// Requires Mosquitto WebSocket listener (e.g., port 9001)

window.MQTTClient = (function(){
  let client = null, connected = false;
  let subscriptions = new Map(); // topic -> Set(callbacks)

  const defaultCfg = {
    host: (window.MQTT_WS_HOST || location.hostname),
    port: (window.MQTT_WS_PORT || 9001),
    protocol: (location.protocol === 'https:' ? 'wss' : 'ws'),
    username: null, password: null, clientId: 'web_' + Math.random().toString(16).slice(2),
    keepalive: 30, reconnectPeriod: 2000
  };

  function connect(cfg={}){
    if(client) try{ client.end(true); }catch(e){}
    const c = Object.assign({}, defaultCfg, cfg);
    const url = `${c.protocol}://${c.host}:${c.port}`;
    client = mqtt.connect(url, {
      clientId: c.clientId,
      username: c.username || undefined,
      password: c.password || undefined,
      keepalive: c.keepalive,
      reconnectPeriod: c.reconnectPeriod,
      clean: true
    });

    client.on('connect', () => {
      connected = true;
      console.log('[mqtt] connected');
      // resubscribe
      for (const topic of subscriptions.keys()) client.subscribe(topic, {qos: 1});
    });
    client.on('reconnect', ()=> console.log('[mqtt] reconnecting...'));
    client.on('close', ()=> { connected = false; console.log('[mqtt] closed'); });
    client.on('error', (err)=> console.warn('[mqtt] error', err?.message||err));
    client.on('message', (topic, payload)=>{
      const text = payload.toString();
      const cbs = subscriptions.get(topic);
      if(cbs) for (const cb of cbs) try{ cb(topic, text); } catch(e){ console.error(e); }
    });
  }

  function subscribe(topic, cb){
    if(!subscriptions.has(topic)) subscriptions.set(topic, new Set());
    subscriptions.get(topic).add(cb);
    if(connected) client.subscribe(topic, {qos:1});
    return () => { // unsubscribe handle
      const set = subscriptions.get(topic);
      if(!set) return;
      set.delete(cb);
      if(set.size===0){
        subscriptions.delete(topic);
        if(connected) client.unsubscribe(topic);
      }
    };
  }

  function publish(topic, message, opts={qos:1, retain:false}){
    if(connected){
      client.publish(topic, message, opts);
    } else {
      // offline queue (very simple) – store to localStorage and retry
      OfflineCache.enqueue({type:'mqtt_pub', topic, message, opts, ts:Date.now()});
    }
  }

  return { connect, subscribe, publish };
})();

