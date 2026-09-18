const fs=require('fs');
const sharp=require('C:/Users/ACER/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/sharp');
(async()=>{
const dir='storage/app/public/gacha-banners',W=960,H=540,N=48;
const source=await sharp(dir+'/mizuki-mystery-base.png').resize(W+24,H+14,{fit:'cover'}).png().toBuffer();
await sharp(dir+'/mizuki-mystery-base.png').resize(W,H).webp({quality:86}).toFile(dir+'/mizuki-mystery-still.webp');
const frames=[];
for(let i=0;i<N;i++){
 const t=i/N*Math.PI*2;
 const sparks=Array.from({length:40},(_,j)=>{const phase=j*2.39996;const x=530+Math.sin(phase)*260+Math.sin(t+phase)*12;const y=270+Math.cos(phase*1.71)*205+Math.cos(t+phase)*20;const a=.18+.65*(.5+.5*Math.sin(t+phase));const r=j%5===0?2:1;return `<circle cx="${x}" cy="${y}" r="${r}" fill="${j%3===0?'#83eaff':'#ffd08a'}" opacity="${a}"/>`;}).join('');
 const overlay=Buffer.from(`<svg width="${W}" height="${H}" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="glow"><stop stop-color="#ffc26b" stop-opacity="${.035+.07*(.5+.5*Math.sin(t))}"/><stop offset="1" stop-color="#ffc26b" stop-opacity="0"/></radialGradient></defs><ellipse cx="630" cy="265" rx="245" ry="170" fill="url(#glow)"/>${sparks}</svg>`);
 frames.push(await sharp(source).extract({left:12+Math.round(4*Math.sin(t)),top:7+Math.round(3*Math.cos(t)),width:W,height:H}).composite([{input:overlay}]).removeAlpha().raw().toBuffer());
}
await sharp(Buffer.concat(frames),{raw:{width:W,height:H*N,channels:3,pageHeight:H}}).webp({quality:68,effort:4,loop:0,delay:Array(N).fill(100)}).toFile(dir+'/mizuki-mystery-animated.webp');
const meta=await sharp(dir+'/mizuki-mystery-animated.webp',{animated:true}).metadata();
console.log(JSON.stringify({frames:meta.pages,width:meta.width,pageHeight:meta.pageHeight,duration:meta.delay?.reduce((a,b)=>a+b,0),bytes:fs.statSync(dir+'/mizuki-mystery-animated.webp').size}));
})();
