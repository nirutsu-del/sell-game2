const {chromium}=require('C:/Users/ACER/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'});
 const results=[];
 for(const width of [320,390,768,1440]){
 const page=await browser.newPage({viewport:{width,height:900},deviceScaleFactor:1});
 const errors=[]; page.on('pageerror',e=>errors.push(e.message));
 await page.goto('http://localhost/sell-game2/public/',{waitUntil:'networkidle'});
 await page.screenshot({path:`previews/responsive-audit/before-${width}.png`,fullPage:true});
 results.push({width,...await page.evaluate(()=>({scrollWidth:document.documentElement.scrollWidth,overflow:[...document.querySelectorAll('body *')].filter(e=>{const r=e.getBoundingClientRect();return r.width>0&&(r.right>innerWidth+1||r.left< -1)&&getComputedStyle(e).position!=='absolute'}).slice(0,12).map(e=>({tag:e.tagName,class:e.className})),headerHeight:document.querySelector('.site-header').getBoundingClientRect().height})),errors});await page.close();
 }console.log(JSON.stringify(results));await browser.close();
})();
