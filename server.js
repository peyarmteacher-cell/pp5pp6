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

// --- Mock Data Store ---
let mockUsers = [
    { id: 1, username: '0000000000000', name: 'Super Admin System', role: 'super_admin', is_approved: 1, school_id: null, affiliation: 'สพป.บุรีรัมย์ เขต 3', position: 'ผู้ดูแลระบบ', is_academic: 0 },
    { id: 2, username: '1111111111111', name: 'School Admin', role: 'admin', is_approved: 1, school_id: 1, school_name: 'โรงเรียนบ้านหนองบัว', affiliation: 'สพป.บุรีรัมย์ เขต 3', position: 'ผู้อำนวยการ', is_academic: 0 },
    { id: 3, name: 'คุณครูสมชาย ใจดี', position: 'ครูผู้ช่วย', is_approved: 1, role: 'teacher', is_academic: 0, school_id: 1 },
    { id: 4, name: 'คุณครูสมหญิง รักเรียน', position: 'ครู ค.ศ. 1', is_approved: 1, role: 'teacher', is_academic: 1, school_id: 1 },
    { id: 5, name: 'คุณครูมานะ ขยัน', school_name: 'โรงเรียนบ้านหนองบัว', position: 'ครูผู้ช่วย', role: 'teacher', is_approved: 0, school_id: 1 }
];

// --- Mock API (จำลอง PHP เพื่อให้ทดสอบในหน้า Preview ได้) ---
app.post('/api/login.php', (req, res) => {
    const { username, password } = req.body;
    const user = mockUsers.find(u => u.username === username && password === '123456');
    if (user) {
        res.json(user);
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
    const schoolId = parseInt(req.query.school_id);
    const teachers = mockUsers.filter(u => u.school_id === schoolId && u.is_approved === 1);
    res.json(teachers);
});

app.get('/api/get_pending_users.php', (req, res) => {
    const pending = mockUsers.filter(u => u.is_approved === 0);
    res.json(pending);
});

app.post('/api/approve_user.php', (req, res) => {
    const { user_id, role } = req.body;
    const user = mockUsers.find(u => u.id === parseInt(user_id));
    if (user) {
        user.is_approved = 1;
        if (role) user.role = role;
        res.json({ message: 'อนุมัติผู้ใช้งานสำเร็จแล้ว (Mock)' });
    } else {
        res.status(404).json({ error: 'ไม่พบผู้ใช้งาน' });
    }
});

app.post('/api/admin/promote_to_admin.php', (req, res) => {
    const { user_id } = req.body;
    const user = mockUsers.find(u => u.id === parseInt(user_id));
    if (user) {
        user.role = 'admin';
        res.json({ message: 'กำหนดสิทธิ์เป็น Admin โรงเรียนสำเร็จแล้ว (Mock)' });
    } else {
        res.status(404).json({ error: 'ไม่พบผู้ใช้งาน' });
    }
});

app.post('/api/admin/set_academic_role.php', (req, res) => {
    const { user_id, is_academic } = req.body;
    const user = mockUsers.find(u => u.id === parseInt(user_id));
    if (user) {
        user.is_academic = is_academic ? 1 : 0;
        res.json({ message: 'ปรับปรุงสิทธิ์งานวิชาการสำเร็จแล้ว (Mock)' });
    } else {
        res.status(404).json({ error: 'ไม่พบผู้ใช้งาน' });
    }
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

// --- Static File Serving with Mock PHP Replacement ---
const servePhpAsHtml = (filePath, res) => {
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        
        // จำลองการแทนที่ตัวแปร PHP พื้นฐานสำหรับหน้า Preview
        // ในระบบจริง PHP จะจัดการส่วนนี้เอง
        const mockSession = {
            user_id: 2,
            name: 'School Admin',
            role: 'admin',
            school_id: 1,
            school_name: 'โรงเรียนบ้านหนองบัว',
            affiliation: 'สพป.บุรีรัมย์ เขต 3',
            is_academic: 0
        };

        // แทนที่ <?= ... ?>
        content = content.replace(/<\?=\s*\$_SESSION\['(.*?)'\]\s*\?>/g, (match, key) => {
            return mockSession[key] !== undefined ? mockSession[key] : '';
        });
        content = content.replace(/<\?=\s*\$username\s*\?>/g, mockSession.name);
        content = content.replace(/<\?=\s*\$role\s*\?>/g, mockSession.role);
        content = content.replace(/<\?=\s*\$school_name\s*\?>/g, mockSession.school_name);
        content = content.replace(/<\?=\s*\$affiliation\s*\?>/g, mockSession.affiliation);
        content = content.replace(/<\?=\s*mb_substr\(\$username,\s*0,\s*1\)\s*\?>/g, mockSession.name.charAt(0));

        // แทนที่เงื่อนไข <?php if ($role === '...'): ?> ... <?php endif; ?>
        // แบบง่ายๆ สำหรับการทดสอบ
        const role = mockSession.role;
        const is_academic = mockSession.is_academic;

        // จัดการบล็อก if/endif
        const processIfBlocks = (text) => {
            // Super Admin
            text = text.replace(/<\?php\s*if\s*\(\$role\s*===\s*'super_admin'\):\s*\?>(.*?)<\?php\s*endif;\s*\?>/gs, (match, inner) => {
                return role === 'super_admin' ? inner : '';
            });
            // Admin
            text = text.replace(/<\?php\s*if\s*\(\$role\s*===\s*'admin'\):\s*\?>(.*?)<\?php\s*endif;\s*\?>/gs, (match, inner) => {
                return role === 'admin' ? inner : '';
            });
            // Teacher
            text = text.replace(/<\?php\s*if\s*\(\$role\s*===\s*'teacher'.*?\):\s*\?>(.*?)<\?php\s*endif;\s*\?>/gs, (match, inner) => {
                return role === 'teacher' ? inner : '';
            });
            // Teacher or Admin
            text = text.replace(/<\?php\s*if\s*\(\$role\s*===\s*'teacher'\s*\|\|\s*\$role\s*===\s*'admin'\):\s*\?>(.*?)<\?php\s*endif;\s*\?>/gs, (match, inner) => {
                return (role === 'teacher' || role === 'admin') ? inner : '';
            });
            return text;
        };

        content = processIfBlocks(content);

        // ลบแท็ก PHP อื่นๆ ที่เหลือ
        content = content.replace(/<\?php.*?\?>/gs, '');

        res.setHeader('Content-Type', 'text/html');
        res.send(content);
    } else {
        res.status(404).send('File not found.');
    }
};

app.get('/', (req, res) => {
    servePhpAsHtml(path.join(__dirname, 'index.php'), res);
});

app.get('/dashboard.php', (req, res) => {
    servePhpAsHtml(path.join(__dirname, 'dashboard.php'), res);
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`Preview server running on http://localhost:${PORT}`);
});
