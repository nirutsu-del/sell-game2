# ผลตรวจระบบ 17 กันยายน 2026

อัปเดตหลังได้รับอนุมัติ: แก้ทั้งสามประเด็นและใช้ migration บนเครื่องนี้แล้ว ดู [ผลแก้ไขและข้อจำกัดการตรวจ MariaDB](system-audit-fixes-2026-09-17.md) ข้อมูลด้านล่างเป็นผลก่อนแก้

ตรวจโค้ด routes/controllers/services/models/migrations/config และชุดทดสอบของโปรเจกต์บนเครื่องนี้ ไม่ได้แก้ business logic หรือทำธุรกรรมกับข้อมูลร้านจริง

## ประเด็นที่ยืนยันได้

### P1: อนุมัติเลขอ้างอิงการโอนเดิมได้หลายรายการ

- ตำแหน่ง: `app/Http/Controllers/Admin/TopupController.php:51`
- `transfer_reference` ตรวจเพียงรูปแบบและบันทึกใน JSON ไม่มีการกันซ้ำข้ามรายการหรือ unique constraint สำหรับเลขธุรกรรมจริง
- จำลองรายการ pending สองรายการ ยอดละ 100 แล้วแอดมินอนุมัติทั้งคู่ด้วย `SAME-BANK-TRANSFER`: ทั้งสองตอบ 303 และ Wallet เพิ่ม 200
- การตรวจ hash สลิปกันเฉพาะไฟล์ที่มี bytes เหมือนกัน จึงไม่แทนการตรวจเลขธุรกรรมของธนาคาร
- ควรมีคอลัมน์/ตารางสำหรับตัวตนธุรกรรมจริง กำหนดขอบเขตเลขอ้างอิงตามช่องทาง/ผู้ให้บริการ และ unique constraint ภายใน transaction เดียวกับการเพิ่มเครดิต พร้อมทดสอบหลายผู้ใช้และคำขอพร้อมกัน
- ประเด็นนี้ต้องมีการอนุมัติจากแอดมิน ไม่ใช่ผู้ใช้เพิ่มเครดิตเองได้โดยตรง

### P2: เซิร์ฟเวอร์ยอมรับการเติมเงินโดยไม่มีสลิป

- ตำแหน่ง: `app/Http/Controllers/TopupController.php:29`
- หน้า Wallet บังคับ `required` แต่ backend ใช้ `nullable|image|max:5120`
- จำลอง POST ที่มี request_id, amount และ payment_method แต่ไม่มี slip: ได้ 303 และสร้าง pending ที่ slip_path เป็น null
- ควรบังคับสลิปที่ backend ให้ตรงกับช่องทางปัจจุบัน และให้รายการที่ไม่มีหลักฐานตอบ validation error
- ยังไม่เพิ่มเครดิตอัตโนมัติ แต่ทำให้มีรายการรอตรวจที่ไม่มีหลักฐานและเพิ่มภาระหลังบ้าน

### P2: ขอผลสุ่มเดิมหลังลบกล่องได้ 404

- ตำแหน่ง: `app/Http/Controllers/CheckoutController.php:23`
- StoreService คืน receipt เดิมได้ แต่ controller เรียก GachaBox::findOrFail เพื่อสร้าง next_rewards ซึ่งไม่พบกล่องที่ soft delete แล้ว
- จำลองสุ่มสำเร็จ ลบกล่อง แล้วส่ง request_id เดิม: ได้ 404, มี spin เดียว และยอดคงเหลือ 75 จากเริ่มต้น 100/ค่าสุ่ม 30/รางวัล 5
- ไม่มีการหักเงินซ้ำในกรณีที่ตรวจ แต่ผู้ใช้กู้ผลผ่านคำขอเดิมไม่ได้ โดยเฉพาะเมื่อ response แรกขาดหาย
- ควรคืน receipt ที่บันทึกไว้โดยไม่ขึ้นกับกล่องที่ยังเปิดอยู่ และคืน next_rewards ว่างเมื่อกล่องถูกลบ

## ความพร้อมก่อนเปิดใช้งานจริง

`php artisan store:security-check` ผ่าน 3/9 ข้อ และไม่ผ่าน 6 ข้อ:

- environment ยังไม่ใช่ production
- debug ยังเปิด
- APP_URL ไม่ใช่ HTTPS
- session secure cookie ยังไม่เปิด
- บัญชีฐานข้อมูลไม่ผ่านเกณฑ์ non-root พร้อมรหัสผ่าน
- mail transport ยังเป็น log/array จึงยังไม่ใช่การส่งอีเมลจริง

รายการเหล่านี้เป็นค่าของเครื่องพัฒนาที่ตรวจ ไม่ได้ยืนยันว่าเซิร์ฟเวอร์จริงตั้งค่าเหมือนกัน ควรตั้งค่า production แยกและตรวจการส่งอีเมล reset password จริงก่อนเปิดร้าน

`config/store.php` ยังตั้ง demo_mode=true และ service_catalog_enabled=false ควรทบทวนให้ตรงขอบเขตเปิดร้าน

## ผลตรวจ

- ชุดทดสอบหลัก: 93 tests ผ่าน / 975 assertions
- ชุดจำลองยืนยันปัญหา: 3 tests / 9 assertions; tests เหล่านี้ยืนยันพฤติกรรมบกพร่องปัจจุบัน ไม่ได้หมายถึงแก้แล้ว
- `npm run build`: ผ่าน
- `composer audit`: ไม่พบ advisories หรือ abandoned packages ณ เวลาตรวจ
- `npm audit`: 0 vulnerabilities ณ เวลาตรวจ
- `php artisan migrate:status`: migrations ทั้งหมด Ran
- Browser checks ของ Wallet/Gacha: ผ่านที่ 390 และ 1440px; ไม่พบ JavaScript page errors หรือ CSP violations ในกรณีที่ทดสอบ

## ขอบเขตและข้อเสนอแนะ

- Tests และ browser fixtures ใช้ SQLite ในหน่วยความจำ; browser จำลอง response การสุ่ม ไม่ใช่การชำระเงินจริง
- ยังไม่ได้ยืนยัน concurrency บน MySQL, SMTP delivery, HTTPS production, mobile hardware หรือการกู้คืน backup รอบนี้
- แนะนำแก้การอนุมัติเลขโอนซ้ำก่อน ตามด้วย validation สลิปและ receipt recovery
- เพิ่ม regression tests สำหรับทั้งสามปัญหา และตรวจ concurrency ของการอนุมัติ/ซื้อ/สุ่มบน MySQL แยกจากฐานข้อมูลร้าน
- ตรวจว่า scheduler สำรองข้อมูลทำงานจริง และทดสอบ restore จากสำเนานอกเครื่องพร้อม APP_KEY เดิม
- เพิ่ม CI ให้รัน tests/build/audit เมื่อเปลี่ยนโค้ด และปรับ README จาก Laravel template เป็นคู่มือของร้าน
- Browser coverage รอบนี้จำกัดที่ Wallet/Gacha; ไม่ใช่การตรวจทุกหน้าด้วยสายตาหรือการรับรองว่าไม่มีบั๊กอื่น

หลังแก้ได้แทนสคริปต์จำลองข้อบกพร่องเดิมด้วย regression tests ใน `tests/Feature/TopupTransferReferenceTest.php`, `SlipReviewTest.php` และ `GachaReceiptTest.php`
