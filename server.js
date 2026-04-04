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
            is_approved: 1
        });
    } else {
        res.status(401).json({ error: 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (ลองใช้ 0000000000000 / 123456)' });
    }
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
