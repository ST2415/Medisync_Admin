# Medisync Admin

ระบบจัดการข้อมูลสำหรับแอปพลิเคชัน **Medisync – Smart Pill & Medical Logbook** ซึ่งเป็นระบบที่พัฒนาขึ้นเพื่อช่วยจัดการข้อมูลยาและประวัติสุขภาพส่วนบุคคล โดยส่วน Admin ใช้สำหรับจัดการข้อมูลผ่าน Web Application

## 1. Project Overview

**Medisync** เป็นระบบที่มีแนวคิดในการช่วยให้ผู้ใช้งานสามารถจัดการข้อมูลเกี่ยวกับยาและประวัติสุขภาพได้อย่างเป็นระบบ ลดปัญหาการลืมรับประทานยา การรับประทานยาซ้ำซ้อน และช่วยให้การจัดเก็บข้อมูลสุขภาพมีความสะดวกมากขึ้น

Repository นี้เป็นส่วนของ **Medisync Admin** ซึ่งประกอบด้วย Web Application และระบบฐานข้อมูลสำหรับจัดเก็บข้อมูล

## 2. Technologies Used

เครื่องมือและเทคโนโลยีที่ใช้ในการพัฒนาระบบ

| เครื่องมือ | รายละเอียด |
|---|---|
| PHP 8.2 | ใช้พัฒนาระบบ Web Application |
| Apache | Web Server สำหรับให้บริการเว็บไซต์ |
| MySQL 8.0 | ระบบจัดการฐานข้อมูล |
| phpMyAdmin | เครื่องมือจัดการฐานข้อมูลผ่าน Web Browser |
| Docker | ใช้จำลองสภาพแวดล้อมในการทำงาน |
| Docker Compose | ใช้จัดการและเริ่มต้น Containers |
| HTML / CSS / JavaScript | ใช้พัฒนาส่วนติดต่อผู้ใช้งาน |
| Git / GitHub | ใช้จัดเก็บและควบคุมเวอร์ชัน Source Code |

## 3. System Requirements

เครื่องคอมพิวเตอร์ที่ต้องการติดตั้งระบบควรมีเครื่องมือดังต่อไปนี้

- Docker Desktop
- Git
- Web Browser เช่น Google Chrome
- โปรแกรมแก้ไข Source Code เช่น Visual Studio Code

## 4. Project Structure

โครงสร้างไฟล์หลักภายใน Repository

```text
Medisync_Admin/
│
├── assets/              # ไฟล์ประกอบการทำงานของระบบ
│
├── sql-export/          # ไฟล์สำรองฐานข้อมูล SQL
│
├── www/                 # Source Code ของ Web Application
│
├── Dockerfile           # กำหนดสภาพแวดล้อม PHP และ Apache
│
├── docker-compose.yml   # กำหนดการทำงานของ Containers
│
└── README.md            # เอกสารแนะนำระบบและวิธีติดตั้ง
```

## 5. Installation Guide

### 5.1 Clone Repository

เปิด Terminal และใช้คำสั่งต่อไปนี้เพื่อดาวน์โหลด Source Code

```bash
git clone https://github.com/ST2415/Medisync_Admin.git
```

เข้าไปยังโฟลเดอร์โปรเจกต์

```bash
cd Medisync_Admin
```

### 5.2 Start Docker

ตรวจสอบให้แน่ใจว่า Docker Desktop เปิดใช้งานอยู่ จากนั้นใช้คำสั่ง

```bash
docker compose up -d --build
```

คำสั่งนี้จะสร้างและเริ่มต้น Containers ที่จำเป็นต่อระบบ ได้แก่

- **db:** MySQL Database
- **phpmyadmin:** เครื่องมือจัดการฐานข้อมูล
- **web:** PHP และ Apache Web Server

ตรวจสอบสถานะ Containers ด้วยคำสั่ง

```bash
docker compose ps
```

### 5.3 Access the System

หลังจากเริ่มต้น Containers สำเร็จ สามารถเข้าใช้งานผ่าน Web Browser ได้ที่

- Web Application: http://localhost:8000
- phpMyAdmin: http://localhost:8080

## 6. Database Configuration

ระบบใช้ MySQL 8.0 เป็นฐานข้อมูล โดยมีการกำหนดค่าการทำงานไว้ในไฟล์ `docker-compose.yml`

ข้อมูลการตั้งค่าฐานข้อมูลประกอบด้วย

| รายการ | ค่า |
|---|---|
| Database Management System | MySQL 8.0 |
| Database Name | medisync |
| Database Port | 3306 |
| Database Host (ภายใน Docker) | db |

หมายเหตุ: ชื่อผู้ใช้และรหัสผ่านสำหรับฐานข้อมูลต้องกำหนดแยกในเครื่องที่ติดตั้ง ไม่ควรนำรหัสผ่านจริงมาเผยแพร่ใน Repository

### 6.1 Import SQL Database

หากต้องการนำเข้าฐานข้อมูลไปใช้งานบนเครื่องอื่น สามารถทำตามขั้นตอนดังนี้

1. เปิด phpMyAdmin ผ่าน http://localhost:8080
2. เข้าสู่ระบบด้วยข้อมูลที่กำหนดไว้ใน Docker
3. เลือกฐานข้อมูล `medisync`
4. เลือกเมนู Import
5. กด Choose File
6. เลือกไฟล์ SQL จากโฟลเดอร์ `sql-export`
7. กด Import เพื่อเริ่มนำเข้าฐานข้อมูล
8. ตรวจสอบว่าตารางและข้อมูลถูกนำเข้าเรียบร้อยแล้ว

**หมายเหตุ:** ต้องตรวจสอบชื่อไฟล์ SQL ที่อยู่ในโฟลเดอร์ `sql-export` ก่อนนำเข้าใช้งาน

## 7. Database Connection

ระบบ Web Application เชื่อมต่อกับ MySQL ผ่าน Docker Network โดยใช้ชื่อ Service `db` เป็น Host และ Port `3306`

ตัวอย่างการเชื่อมต่อฐานข้อมูลด้วย PHP (MySQLi)

```php
<?php

$host = "db";
$username = "YOUR_DB_USERNAME";
$password = "YOUR_DB_PASSWORD";
$database = "medisync";

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Database connection failed");
}

$conn->set_charset("utf8mb4");

?>
```

หมายเหตุ:
- ให้กำหนด Username และ Password ให้ตรงกับการตั้งค่า MySQL ในเครื่อง
- เมื่อ PHP ทำงานภายใน Docker Container ให้ใช้ Host เป็น `db`
- ไม่ควรใช้ `localhost` เป็น Host สำหรับเชื่อมต่อ MySQL ระหว่าง Containers

## 8. Stop the System

หากต้องการหยุดการทำงานของระบบ สามารถใช้คำสั่ง

```bash
docker compose down
```

หากต้องการเริ่มต้นระบบอีกครั้ง ให้ใช้คำสั่ง

```bash
docker compose up -d
```

ข้อมูลฐานข้อมูลจะถูกจัดเก็บผ่าน Docker Volume เพื่อให้ยังคงอยู่หลังจากหยุด Containers ตามปกติ

## 9. Security and Configuration

เพื่อความปลอดภัยในการส่งมอบ Source Code ควรตรวจสอบรายการต่อไปนี้

- ไม่เผยแพร่ Username และ Password ที่ใช้จริง
- ไม่จัดเก็บ API Keys หรือ Secret Keys ภายใน Source Code
- ใช้ไฟล์ Environment Variables สำหรับจัดเก็บข้อมูลสำคัญ
- ตรวจสอบไฟล์ Configuration ก่อนอัปโหลดขึ้น GitHub
- ไม่อัปโหลดข้อมูลส่วนบุคคลหรือข้อมูลสุขภาพจริงของผู้ใช้งาน
- ตรวจสอบสิทธิ์การเข้าถึง Repository ให้ตรงตามข้อกำหนดของผู้สอน

## 10. Repository

GitHub Repository:

https://github.com/ST2415/Medisync_Admin

Repository นี้ใช้สำหรับจัดเก็บ Source Code ของ Medisync Admin รวมถึงไฟล์ที่จำเป็นสำหรับการติดตั้งและไฟล์ฐานข้อมูลสำหรับนำไปใช้งานบนเครื่องอื่น

---

**Project:** Medisync Admin  
**Project Type:** Web Application / Database Management  
**Database:** MySQL 8.0  
**Containerization:** Docker