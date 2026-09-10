# การเปิดใช้งานอีเมลรีเซ็ตรหัสผ่าน

ระบบใช้ Laravel Password Broker: ลิงก์หมดอายุใน 60 นาที ใช้ครั้งเดียว และจำกัดการขอลิงก์ซ้ำ
ปัจจุบัน MAIL_MAILER=log เป็นโหมดพัฒนา ไม่ส่งอีเมลจริง อย่าเปิดโหมด log สำหรับใช้งานจริง เพราะเนื้อหาอีเมลและลิงก์จะถูกเก็บใน log

ตั้งค่าใน .env ด้วยข้อมูลผู้ให้บริการอีเมลของร้าน:

    APP_URL=https://your-store.example
    MAIL_MAILER=smtp
    MAIL_HOST=your-smtp-host
    MAIL_PORT=587
    MAIL_USERNAME=your-smtp-username
    MAIL_PASSWORD=your-smtp-secret
    MAIL_FROM_ADDRESS=no-reply@your-store.example
    MAIL_FROM_NAME="ชื่อร้าน"

ใช้ port และ MAIL_SCHEME ตามที่ผู้ให้บริการระบุ (เช่น smtps สำหรับ implicit TLS)
APP_URL ต้องตรง URL ร้านและรวมโฟลเดอร์ย่อยถ้ามี สำหรับเครื่องพัฒนาอาจใช้ http://127.0.0.1:8000
ไม่ควรบันทึกความลับลง Git หรือส่งรหัสผ่าน SMTP ผ่านแชต

หลังแก้ไขรัน php artisan config:clear แล้วทดสอบขอลิงก์ด้วยบัญชีทดสอบของร้าน
ตรวจว่าอีเมลได้รับจริง ลิงก์เปิดโดเมนถูกต้อง และรีเซ็ตสำเร็จ ลิงก์ที่ใช้แล้วต้องใช้ซ้ำไม่ได้
ชุดทดสอบอัตโนมัติใช้ Notification fake ไม่ส่งอีเมลออกจริง
