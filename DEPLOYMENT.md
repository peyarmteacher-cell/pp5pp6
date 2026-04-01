# คำแนะนำการติดตั้งและอัพโหลดไปยัง Hosting (Lotus)

## 1. การเตรียมฐานข้อมูล (MySQL)
- เข้าไปที่ Control Panel ของ Hosting (เช่น DirectAdmin หรือ cPanel)
- สร้างฐานข้อมูลใหม่ (Database Name, Username, Password)
- นำคำสั่ง SQL จากไฟล์ `database.sql` ไปรันใน phpMyAdmin เพื่อสร้างตารางข้อมูล

## 2. การตั้งค่า Environment Variables
- สร้างไฟล์ `.env` บน Server (หรือตั้งค่าผ่านเมนู Environment Variables ใน Hosting)
- กำหนดค่าดังนี้:
  ```env
  DB_HOST=localhost
  DB_USER=ชื่อผู้ใช้ฐานข้อมูล
  DB_PASSWORD=รหัสผ่านฐานข้อมูล
  DB_NAME=ชื่อฐานข้อมูล
  DB_PORT=3306
  NODE_ENV=production
  ```

## 3. การอัพโหลดโค้ด
- รันคำสั่ง `npm run build` เพื่อสร้างไฟล์สำหรับ Production (โฟลเดอร์ `dist`)
- อัพโหลดไฟล์ทั้งหมด (ยกเว้น `node_modules`) ขึ้นไปยัง Server ผ่าน FTP หรือ File Manager
- หาก Hosting รองรับ Node.js (เช่น ผ่าน Passenger หรือ CloudLinux):
  - กำหนด Entry Point ไปที่ `server.ts` (หรือ `server.js` หากคอมไพล์แล้ว)
  - รันคำสั่ง `npm install` บน Server

## 4. การรันแอปพลิเคชัน
- หากใช้ Node.js Hosting: สั่ง Start Application ผ่านเมนูใน Hosting
- หากใช้ VPS: ใช้ PM2 ในการรัน เช่น `pm2 start server.ts --interpreter tsx`
