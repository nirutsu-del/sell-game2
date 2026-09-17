from PIL import Image, ImageDraw, ImageFilter
from pathlib import Path
import math, random, json, shutil

ROOT=Path(__file__).parent
SRC=Path(r'C:\Users\ACER\.codex\generated_images\01a08d0e-7ee7-7b51-8864-fff1fa13f809')
FILES={'free-fire':'exec-86af0cfc-da9a-4c66-b379-4a4a02522e44.png','roblox':'exec-fac5d948-de2c-4818-b054-73541fbfb072.png','rov':'exec-19e916f7-e681-4e60-aab0-372769bcf25d.png','valorant':'exec-cc12cc2b-17be-45a3-aa20-3bc70d9b8a63.png'}
N=60
S=640
report=[]
for seed,(name,source) in enumerate(FILES.items()):
    shutil.copy2(SRC/source,ROOT/(name+'-base.png'))
    base=Image.open(SRC/source).convert('RGB').resize((S,S),Image.Resampling.LANCZOS)
    large=base.resize((S+24,S+24),Image.Resampling.LANCZOS)
    rng=random.Random(seed)
    particles=[(rng.choice([rng.uniform(.02,.20),rng.uniform(.83,.98)]),rng.uniform(.1,.95),rng.random()*6.28,rng.uniform(1,2.2)) for _ in range(16)]
    frames=[]
    for i in range(N):
        t=2*math.pi*i/N
        dx=3*math.sin(t);dy=(4 if name=='roblox' else 1.8)*math.cos(t)
        frame=large.transform((S,S),Image.Transform.AFFINE,(1,0,12+dx,0,1,12+dy),Image.Resampling.BICUBIC).convert('RGBA')
        glow=Image.new('RGBA',(S,S));d=ImageDraw.Draw(glow)
        pulse=(1-math.cos(t))/2
        d.ellipse((-170,170,140,720),fill=(255,138,61,int(9+10*pulse)))
        d.ellipse((530,60,800,680),fill=(99,220,235,int(8+12*(1-pulse))))
        frame=Image.alpha_composite(frame,glow.filter(ImageFilter.GaussianBlur(45)))
        overlay=Image.new('RGBA',(S,S));d=ImageDraw.Draw(overlay)
        for x,y,p,r in particles:
            px=x*S+5*math.sin(t+p);py=y*S+9*math.cos(t+p)
            a=int(35+85*(.5+.5*math.sin(t+p)))
            color=(99,220,235,a) if x>.5 else (255,164,87,a)
            d.ellipse((px-r,py-r,px+r,py+r),fill=color)
        if name=='valorant':
            for j in range(2):
                pts=[(x,480+j*45+22*math.sin(x/160+t+j)) for x in range(470,640,3)]
                d.line(pts,fill=(125,229,244,45),width=2)
        frames.append(Image.alpha_composite(frame,overlay).convert('RGB'))
    out=ROOT/(name+'-animated.webp')
    frames[0].save(out,save_all=True,append_images=frames[1:],duration=50,loop=0,quality=78,method=4)
    check=Image.open(out);duration=0
    for i in range(check.n_frames):
        check.seek(i);check.load();duration+=check.info.get('duration',0)
    assert check.n_frames>1 and duration==3000 and out.stat().st_size<5_000_000
    report.append({'file':str(out.resolve()),'frames':check.n_frames,'duration_ms':duration,'loop':check.info.get('loop'),'size_bytes':out.stat().st_size,'dimensions':[S,S]})
contact=Image.new('RGB',(1280,1280))
for i,name in enumerate(FILES):
    im=Image.open(ROOT/(name+'-animated.webp')).convert('RGB')
    contact.paste(im,((i%2)*640,(i//2)*640))
contact.save(ROOT/'contact-sheet.jpg',quality=92)
(ROOT/'validation.json').write_text(json.dumps(report,indent=2),encoding='utf8')
print(json.dumps(report,indent=2))
