const {chromium}=require('C:/Users/ACER/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'});
 const results=[];
 for(const width of [320,390,768,1440]){
 const page=await browser.newPage({viewport:{width,height:900},deviceScaleFactor:1,reducedMotion:'reduce'});
 const errors=[];page.on('pageerror',e=>errors.push(e.message));
 await page.goto('http://localhost/sell-game2/public/',{waitUntil:'networkidle'});
 for(const img of await page.locator('main img').all()){await img.scrollIntoViewIfNeeded();await img.evaluate(e=>e.decode().catch(()=>{}));}
 await page.locator('.home-gacha').scrollIntoViewIfNeeded();
 await page.screenshot({path:`previews/responsive-audit/after-gacha-${width}.png`});
 await page.goto('http://localhost/sell-game2/public/#home-howto-title',{waitUntil:'networkidle'});
 const guide=await page.locator('#home-howto-title').boundingBox();
 results.push({width,...await page.evaluate(()=>({scrollWidth:document.documentElement.scrollWidth,brokenImages:[...document.images].filter(e=>e.complete&&!e.naturalWidth).map(e=>e.src),headerBottom:document.querySelector('.site-header').getBoundingClientRect().bottom,chestAnimation:getComputedStyle(document.querySelector('.home-gacha-chest')).animationName})),guideTop:guide.y,errors});
 await page.screenshot({path:`previews/responsive-audit/after-guide-${width}.png`});
 await page.close();
 }console.log(JSON.stringify(results));await browser.close();
})();
