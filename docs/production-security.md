# ตรวจความพร้อมก่อนเปิด Mizuki Shop

รัน `php artisan store:security-check` อ่านค่าโดยไม่แก้ไขหรือพิมพ์ secrets คืน exit code 1 เมื่อยังมีค่าที่ไม่พร้อม ผล PASS ไม่ได้รับรองความปลอดภัยของเซิร์ฟเวอร์

- Production: APP_ENV=production, APP_DEBUG=false, APP_URL เป็น HTTPS ของโดเมนจริง, SESSION_SECURE_COOKIE=true อย่าใช้ secure cookie กับ HTTP localhost
- ใช้ DB user ของร้านที่จำกัดสิทธิ์และมีรหัสผ่าน ไม่ใช้ root (ระบบลืมรหัสผ่านทางอีเมลถูกนำออกแล้ว)
- ตั้ง DocumentRoot ไป public/ เท่านั้น ห้ามเปิดทั้งโปรเจกต์ออกอินเทอร์เน็ต .htaccess ที่ root เป็นเพียงชั้นเสริมสำหรับ Apache ที่เปิด AllowOverride; Nginx ไม่อ่านไฟล์นี้
- ตรวจจาก HTTP จริงว่า .env, .git/config, storage/app/backups และ storage/app/private/slips ได้ 403/404 ไม่ใช่ไฟล์ดาวน์โหลด ทดสอบว่ารูปใน public/storage ยังเปิดได้ PHP feature tests ไม่ได้ทดสอบ Apache
- จำกัดสิทธิ์ไฟล์: ผู้ใช้เว็บเขียนได้เฉพาะ storage และ bootstrap/cache; ปิดการ execute script ใน uploads ที่เว็บเซิร์ฟเวอร์ จำกัด ACL ชุดสำรอง/APP_KEY บน Windows เก็บสำเนาเข้ารหัสนอกเครื่อง
- หลัง reverse proxy ต้อง trust เฉพาะ proxy ที่ดูแลจริง แล้วทดสอบ HTTPS detection และ IP สำหรับ rate limit
- หลังตั้งค่าจริงจึงรัน config:cache และ store:security-check อีกครั้ง ห้ามส่ง .env หรือชุดสำรองเข้า Git

เพิ่ม header ป้องกัน MIME sniffing, จำกัด iframe เป็น same origin, ไม่ส่ง referrer และไม่ cache หน้าบัญชี/แอดมิน/รีเซ็ตรหัสผ่าน HSTS ส่งเฉพาะ production ที่ตรวจพบ HTTPS ยังไม่เพิ่ม CSP แบบเข้มงวด เพราะต้องย้าย inline scripts และทดสอบหน้า Wallet/สุ่มก่อน

ล็อกอินจำกัด 5 ครั้ง/นาทีต่ออีเมล+IP และ 30 ครั้ง/นาทีต่อ IP รวมครั้งที่สำเร็จ สิทธิ์ admin ตรวจฝั่งเซิร์ฟเวอร์ทุก route ในกลุ่ม admin

## ข้อแก้ไขสำหรับการสำรองและกู้คืน

คำสั่ง store:backup รองรับ MySQL/MariaDB เท่านั้น ชุดใหม่รวม storage_public และ private_slips (เมื่อมีไฟล์) ไม่รวม .env ชุดเก่าที่มีเพียง storage_public **ไม่ได้รวมสลิปส่วนตัว**

ชื่อชุดใหม่มี suffix สุ่มป้องกันชนกัน ต้องมี manifest.json จึงถือว่าคำสั่งสำเร็จ ชุดที่ล้มเหลวจะคงไว้ให้ตรวจสอบ ไม่ลบอัตโนมัติ ห้ามใช้กู้คืน ตัวเลือก --output ต้องเป็นโฟลเดอร์ที่มีอยู่แล้วนอกโปรเจกต์ และต้องไม่ถูกเผยแพร่โดยเว็บเซิร์ฟเวอร์ใด ชุดสำรองยังไม่ได้เข้ารหัส รหัสผ่าน MySQL ส่งผ่าน environment ของ process แทน arguments แต่ผู้มีสิทธิ์ระดับระบบยังเข้าถึงได้

เก็บ APP_KEY เดิมแยกในที่ปลอดภัย การถอดรหัสบัญชีเกมต้องใช้คีย์เดิม ห้ามสร้างคีย์ใหม่ทับเมื่อต้องการอ่านข้อมูลเก่า

ทดสอบกู้คืนในสำเนาโปรเจกต์กับฐานข้อมูลว่างแยกจากร้านจริงเท่านั้น เปิด MySQL client เช่น `mysql -u restore_user -p mizuki_restore_test` แล้วพิมพ์ `source D:/private-backups/ชื่อชุด/database.sql;` ภายใน client (ไม่ใช่ PowerShell) คัดลอก storage_public กลับ storage/app/public และ private_slips กลับ storage/app/private/slips ใช้ APP_KEY เดิม ชี้ DB ไปฐานข้อมูลทดสอบ ปิด integration และการส่งอีเมลจริง แล้วตรวจยอด Wallet รายการซื้อ การถอดรหัส รูปและสลิป

ฐานข้อมูลกับไฟล์ไม่ได้เป็น snapshot เดียวกัน ควรหยุดการเขียนชั่วคราวเมื่อต้องการชุดที่สอดคล้องกันเต็มรูปแบบ

Laravel schedule กำหนด 03:00 Asia/Bangkok แต่ต้องติดตั้งตัวเรียก schedule:run ทุกนาทีบนเซิร์ฟเวอร์ก่อน ยังไม่ได้ติดตั้ง Windows Task Scheduler/cron ให้ ทดสอบกู้คืนฐานข้อมูลและไฟล์ในระบบแยกแล้วเมื่อ 8 กันยายน 2026 ดูผลใน [restore-verification.md](restore-verification.md)
