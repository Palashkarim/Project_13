// Tiny offline queue using localStorage for missed actions
window.OfflineCache = (function(){
  const KEY = 'iot_offline_queue';

  function read(){ try{ return JSON.parse(localStorage.getItem(KEY)||'[]'); }catch(e){ return []; } }
  function write(arr){ localStorage.setItem(KEY, JSON.stringify(arr.slice(-200))); } // cap

  function enqueue(item){ const arr = read(); arr.push(item); write(arr); }

  async function flush(){
    const arr = read(); if(!arr.length) return 0;
    const remaining = [];
    for(const it of arr){
      if(it.type==='mqtt_pub'){
        try {
          MQTTClient.publish(it.topic, it.message, it.opts||{});
          // if not connected yet, repush
          if (!window.navigator.onLine) remaining.push(it);
        } catch(e){ remaining.push(it); }
      } else {
        remaining.push(it);
      }
    }
    write(remaining);
    return arr.length - remaining.length;
  }

  window.addEventListener('online', ()=> setTimeout(flush, 500));
  return { enqueue, flush };
})();
