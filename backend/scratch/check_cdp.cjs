const { spawn } = require('child_process');

const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
  '--headless',
  '--remote-debugging-port=9224',
  '--disable-gpu',
  '--window-size=1024,768',
  'http://localhost:8000/dashboard_preview.html'
]);

setTimeout(async () => {
  try {
    const listRes = await fetch('http://127.0.0.1:9224/json');
    const tabs = await listRes.json();
    const target = tabs.find(t => t.url.includes('dashboard_preview.html'));
    const ws = new WebSocket(target.webSocketDebuggerUrl);
    ws.onopen = () => {
      ws.send(JSON.stringify({
        id: 1,
        method: 'Runtime.evaluate',
        params: {
          expression: `(() => {
            const sb = document.getElementById('sidebar');
            const style = window.getComputedStyle(sb);
            return JSON.stringify({
              innerWidth: window.innerWidth,
              innerHeight: window.innerHeight,
              sidebarVisibility: style.visibility,
              sidebarPosition: style.position,
              sidebarTransform: style.transform,
              sidebarRect: sb.getBoundingClientRect(),
              bodyWidth: document.body.clientWidth,
              scrollWidth: document.documentElement.scrollWidth
            });
          })()`
        }
      }));
    };
    ws.onmessage = (msg) => {
      const data = JSON.parse(msg.data);
      console.log('EVAL RESULT:', data.result.result.value);
      chrome.kill();
      process.exit(0);
    };
  } catch(e) {
    console.error(e);
    chrome.kill();
    process.exit(1);
  }
}, 2000);
