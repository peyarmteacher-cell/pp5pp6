import express from 'express';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = 3000;

app.use(express.json());
app.use(express.static(__dirname));

// --- Mock API (จำลอง PHP เพื่อให้ทดสอบในหน้า Preview ได้) ---
app.post('/api/login.php', (req, res) => {
    const { username, password } = req.body;
    // จำลอง Super Admin
    if (username === '0000000000000' && password === '123456') {
        res.json({
            id: 1,
            username: '0000000000000',
            name: 'Super Admin System',
            role: 'super_admin',
            is_approved: 1,
            school_id: null,
            affiliation: 'สพป.บุรีรัมย์ เขต 3'
        });
    } else if (username === '1111111111111' && password === '123456') {
        // จำลอง Admin โรงเรียน
        res.json({
            id: 2,
            username: '1111111111111',
            name: 'School Admin',
            role: 'admin',
            is_approved: 1,
            school_id: 1,
            school_name: 'โรงเรียนบ้านหนองบัว',
            affiliation: 'สพป.บุรีรัมย์ เขต 3'
        });
    } else {
        res.status(401).json({ error: 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (ลองใช้ 0000000000000 / 123456)' });
    }
});

app.get('/api/get_schools.php', (req, res) => {
    res.json([
        { id: 1, code: '10310001', name: 'โรงเรียนบ้านหนองบัว', province: 'บุรีรัมย์' },
        { id: 2, code: '10310002', name: 'โรงเรียนบ้านดอนกลาง', province: 'บุรีรัมย์' }
    ]);
});

app.get('/api/get_school_teachers.php', (req, res) => {
    res.json([
        { id: 3, name: 'คุณครูสมชาย ใจดี', position: 'ครูผู้ช่วย', is_approved: 1, role: 'teacher', is_academic: 0 },
        { id: 4, name: 'คุณครูสมหญิง รักเรียน', position: 'ครู ค.ศ. 1', is_approved: 1, role: 'teacher', is_academic: 1 }
    ]);
});

app.get('/api/get_pending_users.php', (req, res) => {
    res.json([
        { id: 5, name: 'คุณครูมานะ ขยัน', school_name: 'โรงเรียนบ้านหนองบัว', position: 'ครูผู้ช่วย', role: 'teacher' }
    ]);
});

app.post('/api/approve_user.php', (req, res) => {
    res.json({ message: 'อนุมัติผู้ใช้งานสำเร็จแล้ว (Mock)' });
});

app.post('/api/admin/promote_to_admin.php', (req, res) => {
    res.json({ message: 'กำหนดสิทธิ์เป็น Admin โรงเรียนสำเร็จแล้ว (Mock)' });
});

app.post('/api/admin/set_academic_role.php', (req, res) => {
    res.json({ message: 'ปรับปรุงสิทธิ์งานวิชาการสำเร็จแล้ว (Mock)' });
});

app.get('/api/academic/get_students.php', (req, res) => {
    res.json([
        { id: 1, student_code: '66001', name: 'เด็กชายกอไก่ ใจดี', level: 'ป.1' },
        { id: 2, student_code: '66002', name: 'เด็กหญิงขอไข่ ใฝ่เรียน', level: 'ป.1' }
    ]);
});

app.get('/api/academic/get_subjects.php', (req, res) => {
    res.json([
        { id: 1, code: 'ท11101', name: 'ภาษาไทย', level: 'ป.1', hours: 200, credits: 5.0 },
        { id: 2, code: 'ค11101', name: 'คณิตศาสตร์', level: 'ป.1', hours: 200, credits: 5.0 }
    ]);
});

app.post('/api/reject_user.php', (req, res) => {
    res.json({ message: 'ปฏิเสธการสมัครและลบข้อมูลสำเร็จแล้ว (Mock)' });
});

app.get('/api/admin/get_teacher_assignments.php', (req, res) => {
    res.json([
        { assignment_id: 1, code: 'ท11101', name: 'ภาษาไทย', level: 'ป.1', hours: 200, credits: 5.0 }
    ]);
});

app.post('/api/admin/assign_subjects.php', (req, res) => {
    res.json({ message: 'มอบหมายงานสอนสำเร็จแล้ว (Mock)' });
});

app.post('/api/admin/remove_assignment.php', (req, res) => {
    res.json({ message: 'ยกเลิกงานสอนสำเร็จแล้ว (Mock)' });
});

app.post('/api/register.php', (req, res) => {
    res.json({ message: 'จำลองการสมัครสมาชิกสำเร็จ! (ในระบบจริงจะตรวจสอบรหัสโรงเรียน 8 หลัก)' });
});

// --- Static File Serving ---
app.get('/', (req, res) => {
    const indexPath = path.join(__dirname, 'index.php');
    if (fs.existsSync(indexPath)) {
        res.sendFile(indexPath);
    } else {
        res.status(404).send('index.php not found.');
    }
});

app.get('/dashboard.php', (req, res) => {
    const dashPath = path.join(__dirname, 'dashboard.php');
    if (fs.existsSync(dashPath)) {
        res.sendFile(dashPath);
    } else {
        res.status(404).send('dashboard.php not found.');
    }
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`Preview server running on http://localhost:${PORT}`);
});
