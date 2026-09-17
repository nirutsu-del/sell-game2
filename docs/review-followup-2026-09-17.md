# ผลการแก้ไขตาม Code Review — 17 กันยายน 2026

อ้างอิงโค้ดก่อนแก้ไข: `fc4f068298af77db37fe634ca64cba03fbb1a5a3`

## สิ่งที่แก้ไข

- ปรับ `C:/Users/ACER/Downloads/Starter_Prompt_Template.md` ให้แก้ข้ออธิบาย session ที่คลาดเคลื่อน ระบุขอบเขตการตรวจ เกณฑ์ยอมรับ และข้อจำกัดของผลทดสอบ
- การแก้บัญชีเกมของแอดมินอ่านข้อมูลใหม่ภายใน transaction และ `lockForUpdate()` ร่วมกับเส้นทางซื้อ ปฏิเสธการเปิดขายเมื่อมีประวัติขายก่อนเขียนไฟล์ภาพ และยังคง unique constraint ของประวัติซื้อไว้
- ฝั่งซื้อปฏิเสธบัญชีที่มีประวัติขายแม้สถานะเดิมผิดเป็น `available` โดยตอบ validation error และไม่เปลี่ยนยอดเงินหรือประวัติ
- จัดการภาพที่อัปโหลดระหว่างการแก้ไขให้ลบภาพเดิมหลัง transaction สำเร็จ และลบภาพที่เพิ่งอัปโหลดเมื่อ transaction ล้มเหลว โดยใช้ภาพที่อัปโหลดแล้วซ้ำเมื่อ retry
- คง `PasswordController` เดิม หลังทดสอบยืนยันว่า `auth.session` และการเปลี่ยน remember token ปฏิเสธอุปกรณ์และ Remember me เก่าได้หลังเปลี่ยน/รีเซ็ตรหัสผ่าน
- เพิ่ม CSP พร้อมข้อจำกัด object/base/form/frame และอนุญาต origin กับ WebSocket ของ Vite เฉพาะ environment `local` ที่มี hot file
- เพิ่มข้อความถาวรสำหรับโหมดการ์ดและกล่อง และสลับการแสดงข้อความตามโหมด

## ผลทดสอบ

- ก่อนแก้: `php artisan test` ผ่าน 83 tests / 861 assertions
- หลังแก้และเพิ่มกรณีทดสอบ: ผ่าน 93 tests / 975 assertions
- `npm run build` ผ่าน
- `git diff --check` ผ่าน (Git มีเพียงคำเตือนเรื่องแปลง LF/CRLF)
- Edge headless: Wallet และ Gacha ที่ 390 และ 1440 พิกเซล ผ่านทั้ง built assets และ Vite dev server ที่พอร์ต 5177
- Wallet สลับ PromptPay/TrueMoney ได้, Gacha สลับ cards/wheel/box กลับไปมาได้, ข้อความถูกโหมด, แสดงผลรางวัลได้, ไม่พบ horizontal overflow บนหน้า Gacha, JavaScript error หรือ CSP violation ในกรณีที่ตรวจ
- ตรวจภาพหน้าจอของกล่องบนมือถือและการ์ดบนเดสก์ท็อปแล้ว ข้อความอ่านได้และไม่ทับองค์ประกอบอื่น
- ปิด Vite ที่เปิดเพื่อทดสอบ และลบ hot file ของการทดสอบแล้ว

## ขอบเขตและข้อจำกัด

- Feature tests ใช้ SQLite จึงไม่ได้พิสูจน์การทำงานพร้อมกันบน MySQL จริง แม้โค้ดใช้ row lock เดียวกับ checkout
- Session tests ใช้ cookie jars, guard และ session stores แยกกันผ่าน HTTP test kernel สำหรับ `file` และ `database` ไม่ได้ทดสอบ Redis/cookie session driver
- การหมดสิทธิ์ของ file session ตรวจเมื่อเรียกเส้นทางที่มี `auth.session` อีกครั้ง ไม่ได้หมายถึงลบไฟล์ session ทุกเครื่องทันที
- Browser fixtures render จาก Laravel ด้วยฐานข้อมูล SQLite ในหน่วยความจำ ใช้ response จำลองสำหรับการสุ่ม ไม่มีการซื้อหรือหักเงินในฐานข้อมูลร้านจริง
- Browser checks ใช้ reduced motion จึงตรวจการแสดงผลและการเปลี่ยนโหมด แต่ไม่ได้ยืนยันระยะเวลาของแอนิเมชันแบบเต็ม
- CSP ยังใช้ `'unsafe-inline'` เพื่อรองรับสคริปต์และ style ปัจจุบัน จึงไม่ใช่ strict CSP

## รัน browser checks ซ้ำ

1. `npm run build`
2. `php scripts/render-review-fixtures.php`
3. `node scripts/check-review-browser.cjs`

กรณี Vite: เปิด `npm run dev -- --host 127.0.0.1 --port 5177 --strictPort` ในอีก terminal แล้วรัน `php scripts/render-review-fixtures.php http://127.0.0.1:5177` ก่อนตรวจ browser และปิด Vite หลังตรวจ

สคริปต์ browser ใช้พอร์ต 8187 และค่าเริ่มต้นของ Playwright/Edge ตามเครื่องนี้ สามารถกำหนด `PLAYWRIGHT_MODULE` และ `BROWSER_EXECUTABLE` ได้ เก็บ fixtures และภาพหน้าจอใน `storage/app/private/review-browser` ซึ่งไม่ติดตามใน Git
