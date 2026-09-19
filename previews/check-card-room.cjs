const fs=require('fs');
const assert=require('assert');
const {chromium}=require('C:/Users/ACER/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'});
 const base='http://localhost/sell-game2/public';
 const errors=[];
 for(const width of [1440,768,390,320]){
  const page=await browser.newPage({viewport:{width,height:1050},reducedMotion:'reduce'});
  page.on('pageerror',e=>errors.push(e.message));
  await page.goto(base+'/gacha/2',{waitUntil:'networkidle'});
  await page.locator('[data-card-index="2"]').click();
  assert.equal(await page.locator('[data-card-index="2"]').getAttribute('aria-pressed'),'true');
  assert.equal(await page.locator('.room-reward').count(),4);
  assert(!(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth)),`overflow ${width}`);
  await page.screenshot({path:`previews/card-room-${width}.png`,fullPage:true});
  for(const mode of ['wheel','box','cards']){
   await page.locator(`[data-gacha-mode="${mode}"]`).click();
   assert(!(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth)),`${mode} overflow ${width}`);
  }
  console.log(JSON.stringify({width,overflow:false,modes:3}));await page.close();
 }
 const fixture=JSON.parse(fs.readFileSync('storage/app/private/review-browser/gacha.json','utf8'));
 const page=await browser.newPage({viewport:{width:1440,height:1050},reducedMotion:'reduce'});
 page.on('pageerror',e=>errors.push(e.message));
 await page.route(base+'/gacha/1',r=>r.fulfill({contentType:'text/html',body:fixture.html.replaceAll('http://127.0.0.1:8187',base).replaceAll('http:\\/\\/127.0.0.1:8187',base)}));
 let calls=0;
 await page.route(base+'/gacha/1/spin',r=>{calls++;return r.fulfill({json:{success:true,item_id:1,reward_type:'credit',credit_amount:25,new_balance:1000+calls*15,result:'เครดิต ฿25.00',spin_id:100+calls,next_rewards:[{id:1,type:'credit',title:'เครดิต ฿25.00',chance:100,image:null}]}})});
 await page.goto(base+'/gacha/1',{waitUntil:'networkidle'});
 assert(await page.locator('#gacha-spin').isDisabled());
 await page.locator('[data-card-index="2"]').click();
 for(const mode of ['cards','wheel','box']){
  await page.locator(`[data-gacha-mode="${mode}"]`).click();
  await page.locator('#gacha-spin').click();
  await page.locator('#gacha-result').waitFor({state:'visible'});
  assert.equal(await page.locator('.room-reward').count(),1);
  assert.equal(await page.locator('.room-chance').textContent(),'100%');
  await page.locator('#gacha-result').evaluate(e=>e.close());
 }
 assert.equal(calls,3);assert.equal(await page.locator('#gacha-stock').textContent(),'รางวัลเครดิต');
 assert.deepEqual(errors,[]);console.log(JSON.stringify({simulatedSpins:calls,liveSpins:0,errors}));
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
