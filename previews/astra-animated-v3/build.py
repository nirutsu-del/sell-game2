from PIL import Image, ImageDraw, ImageFilter, ImageChops, ImageStat
from pathlib import Path
import math, random, json

ROOT=Path(__file__).parent
SRC=ROOT.parent/'astra-animated-v2'
S=640
N=60
names=['free-fire','roblox','rov','valorant']
report=[]
edge=Image.new('L',(S,S));ed=ImageDraw.Draw(edge)
ed.rectangle((0,0,95,S),fill=180);ed.rectangle((565,0,S,S),fill=180)
edge=edge.filter(ImageFilter.GaussianBlur(35))
vignette=Image.new('L',(S,S))
vignette.putdata([int(75*min(1,((x-320)**2+(y-300)**2)/150000)) for y in range(S) for x in range(S)])
for seed,name in enumerate(names):
    base=Image.open(SRC/(name+'-base.png')).convert('RGB').resize((S,S),Image.Resampling.LANCZOS)
    rng=random.Random(100+seed)
    particles=[(rng.choice([rng.uniform(0,.27),rng.uniform(.82,1)]),rng.random(),rng.random()*math.tau,rng.uniform(1.3,3.2),j%3) for j in range(42)]
    def make_frame(t):
        t=t%math.tau
        pulse=(1-math.cos(t))/2
        zoom=1.075+.024*math.sin(t)
        angle=math.radians(.7*math.sin(t)) if name=='roblox' else 0
        a=math.cos(angle)/zoom;b=math.sin(angle)/zoom
        dx=7*math.sin(t);dy=(8 if name=='roblox' else 3)*math.cos(t)
        frame=base.transform((S,S),Image.Transform.AFFINE,(a,b,320-a*320-b*320+dx,-b,a,320+b*320-a*320+dy),Image.Resampling.BICUBIC)
        bg=base.transform((S,S),Image.Transform.AFFINE,(a,b,320-a*320-b*320-dx*.8,-b,a,320+b*320-a*320-dy*.6),Image.Resampling.BICUBIC)
        frame=Image.composite(bg,frame,edge).convert('RGBA')
        light=Image.new('RGBA',(S,S));d=ImageDraw.Draw(light)
        sweep=80+480*(.5+.5*math.sin(t))
        d.polygon([(sweep-120,0),(sweep-50,0),(sweep+150,S),(sweep+45,S)],fill=(120,214,255,22))
        d.ellipse((-150,240,160,760),fill=(255,130,45,int(20+30*pulse)))
        d.ellipse((490,100,800,690),fill=(73,210,255,int(15+25*(1-pulse))))
        if name=='roblox':d.ellipse((120,475,560,745),fill=(42,225,255,int(30+65*pulse)))
        if name=='valorant':d.ellipse((510,155,635,320),fill=(80,231,255,int(15+65*pulse)))
        frame=Image.alpha_composite(frame,light.filter(ImageFilter.GaussianBlur(25)))
        fx=Image.new('RGBA',(S,S));d=ImageDraw.Draw(fx)
        for x,y,p,r,layer in particles:
            phase=t+p
            px=x*S+(12+layer*8)*math.sin(phase)
            py=y*S+(22+layer*13)*math.cos(phase)
            alpha=int((60+layer*25)*(1+.65*math.sin(phase)))
            color=(95,225,255,alpha) if x>.5 else (255,157,58,alpha)
            if name=='roblox':
                color=[(255,178,40,alpha),(64,219,255,alpha),(135,247,116,alpha)][layer]
                d.polygon([(px,py-r*1.8),(px+r*1.8,py),(px,py+r*1.8),(px-r*1.8,py)],fill=color)
            elif name=='free-fire':
                d.line((px,py,px+3,py-5-layer*2),fill=color,width=max(1,int(r)))
            else:d.ellipse((px-r,py-r,px+r,py+r),fill=color)
        if name=='rov':
            for j in range(2):
                pts=[]
                for k in range(100):
                    ang=math.pi*.1+math.pi*1.1*k/99
                    pts.append((320+(280+j*12)*math.cos(ang),440+(130+j*15)*math.sin(ang)+18*math.sin(t+k/40)))
                d.line(pts,fill=((100,227,255,125) if j==0 else (255,160,76,100)),width=2)
            d.line([(20,385),(110,430),(170,470)],fill=(85,223,255,int(45+90*pulse)),width=5)
        if name=='valorant':
            for j in range(3):
                pts=[(x,425+j*45+32*math.sin(x/125-t+j*.6)) for x in range(-10,660,3)]
                d.line(pts,fill=(140,236,255,75+j*20),width=2+j%2)
            for j in range(4):
                yy=100+j*130+25*math.sin(t+j)
                d.line((555,yy,620,yy-18),fill=(94,227,255,85),width=2)
        frame=Image.alpha_composite(frame,fx.filter(ImageFilter.GaussianBlur(3)))
        frame=Image.alpha_composite(frame,fx)
        shade=Image.new('RGBA',(S,S),(0,4,17));shade.putalpha(vignette.point(lambda v:int(v*(.65+.35*pulse))))
        return Image.alpha_composite(frame,shade).convert('RGB')
    frames=[make_frame(math.tau*i/N) for i in range(N)]
    assert ImageChops.difference(make_frame(0),make_frame(math.tau)).getbbox() is None
    out=ROOT/(name+'-animated.webp')
    for quality in [75,68,60,52]:
        frames[0].save(out,save_all=True,append_images=frames[1:],duration=50,loop=0,quality=quality,method=4)
        if out.stat().st_size<=5_000_000:break
    im=Image.open(out);dur=0;unique=set()
    for i in range(im.n_frames):
        im.seek(i);im.load();dur+=im.info['duration'];unique.add(hash(im.tobytes()))
    assert im.n_frames==60 and dur==3000 and im.info['loop']==0 and len(unique)==60 and out.stat().st_size<=5_000_000
    frames[15].save(ROOT/(name+'-preview.jpg'),quality=90)
    report.append(dict(file=str(out.resolve()),frames=60,unique_frames=len(unique),duration_ms=dur,loop=0,dimensions=list(im.size),size_bytes=out.stat().st_size,quality=quality,periodic_render_verified=True))
    print(json.dumps(report[-1]),flush=True)
(ROOT/'validation.json').write_text(json.dumps(report,indent=2),encoding='utf8')
