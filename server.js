import express from 'express';
import path from 'path';
import { fileURLToPath } from 'url';
import cors from 'cors';
import dotenv from 'dotenv';

import pool from './src/db.js';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

console.log('--- APPLICATION STARTING ---');
console.log('Node Version:', process.version);
console.log('Directory:', __dirname);

async function startServer() {
  const app = express();
  const PORT = process.env.PORT || 3000;

  app.use(cors());
  app.use(express.json({ limit: '50mb' }));
  app.use(express.urlencoded({ extended: true, limit: '50mb' }));

  // Health check for debugging
  app.get('/api/health', (req, res) => {
    res.json({ status: 'ok', time: new Date().toISOString(), node: process.version });
  });

  // --- API Routes with MySQL ---
  
  // Test Database Connection
  pool.getConnection()
    .then(conn => {
      console.log('✅ Database Connected Successfully!');
      conn.release();
    })
    .catch(err => {
      console.error('❌ Database Connection Failed:', err.message);
      console.log('App will continue to run, but database features will be unavailable.');
    });

  // --- Authentication API ---
  
  app.post('/api/login', async (req, res) => {
    const { username, password } = req.body;
    try {
      const [rows] = await pool.query(
        'SELECT u.*, s.name as school_name FROM users u LEFT JOIN schools s ON u.school_id = s.id WHERE u.username = ? AND u.password = ?',
        [username, password]
      );
      
      if (rows.length === 0) {
        return res.status(401).json({ error: 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง' });
      }
      
      const user = rows[0];
      if (!user.is_approved) {
        return res.status(403).json({ error: 'บัญชีของคุณยังไม่ได้รับการอนุมัติ กรุณารอการตรวจสอบ' });
      }
      
      res.json(user);
    } catch (error) {
      console.error('Login Error:', error);
      res.status(500).json({ error: 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ' });
    }
  });

  app.post('/api/register', async (req, res) => {
    const { smissCode, nationalId, name, position, role } = req.body;
    try {
      // Find school by SMISS code
      const [schools] = await pool.query('SELECT id FROM schools WHERE code = ?', [smissCode]);
      if (schools.length === 0) {
        return res.status(404).json({ error: 'ไม่พบรหัสโรงเรียนนี้ในระบบ' });
      }
      
      const schoolId = schools[0].id;
      const defaultPassword = '123456';
      
      await pool.query(
        'INSERT INTO users (username, password, name, role, school_id, national_id, position, is_approved, is_first_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [nationalId, defaultPassword, name, role, schoolId, nationalId, position, false, true]
      );
      
      res.json({ status: 'success', message: 'ส่งคำขอสมัครสมาชิกเรียบร้อยแล้ว กรุณารอการอนุมัติ' });
    } catch (error) {
      console.error('Register Error:', error);
      if (error.code === 'ER_DUP_ENTRY') {
        res.status(400).json({ error: 'เลขประจำตัวประชาชนนี้มีการสมัครใช้งานแล้ว' });
      } else {
        res.status(500).json({ error: 'ไม่สามารถสมัครสมาชิกได้' });
      }
    }
  });

  app.post('/api/change-password', async (req, res) => {
    const { userId, newPassword } = req.body;
    try {
      await pool.query('UPDATE users SET password = ?, is_first_login = FALSE WHERE id = ?', [newPassword, userId]);
      res.json({ status: 'success' });
    } catch (error) {
      res.status(500).json({ error: 'ไม่สามารถเปลี่ยนรหัสผ่านได้' });
    }
  });

  app.get('/api/admin/pending-users', async (req, res) => {
    const { schoolId, role } = req.query;
    try {
      let query = 'SELECT u.*, s.name as school_name FROM users u LEFT JOIN schools s ON u.school_id = s.id WHERE u.is_approved = FALSE';
      let params = [];
      
      if (role === 'admin') {
        query += ' AND u.school_id = ? AND u.role = "teacher"';
        params.push(schoolId);
      } else if (role === 'super_admin') {
        query += ' AND u.role = "admin"';
      }
      
      const [rows] = await pool.query(query, params);
      res.json(rows);
    } catch (error) {
      res.status(500).json({ error: 'ไม่สามารถดึงข้อมูลคำขอได้' });
    }
  });

  app.post('/api/admin/approve-user', async (req, res) => {
    const { userId } = req.body;
    try {
      await pool.query('UPDATE users SET is_approved = TRUE WHERE id = ?', [userId]);
      res.json({ status: 'success' });
    } catch (error) {
      res.status(500).json({ error: 'ไม่สามารถอนุมัติได้' });
    }
  });

  app.get('/api/schools', async (req, res) => {
    try {
      const [rows] = await pool.query('SELECT * FROM schools ORDER BY created_at DESC');
      res.json(rows);
    } catch (error) {
      console.error('Database Error:', error);
      res.status(500).json({ error: 'ไม่สามารถดึงข้อมูลโรงเรียนได้' });
    }
  });

  app.post('/api/schools', async (req, res) => {
    const { name, code, province } = req.body;
    if (!code || code.length !== 8) {
      return res.status(400).json({ error: 'รหัสโรงเรียนต้องมี 8 หลัก' });
    }
    try {
      await pool.query('INSERT INTO schools (name, code, province) VALUES (?, ?, ?)', [name, code, province]);
      res.json({ status: 'success', message: 'เพิ่มโรงเรียนเรียบร้อย' });
    } catch (error) {
      console.error('Database Error:', error);
      res.status(500).json({ error: 'ไม่สามารถเพิ่มข้อมูลโรงเรียนได้' });
    }
  });

  app.get('/api/teachers', async (req, res) => {
    try {
      const [rows] = await pool.query('SELECT * FROM users WHERE role = "teacher"');
      res.json(rows);
    } catch (error) {
      console.error('Database Error:', error);
      res.status(500).json({ error: 'ไม่สามารถดึงข้อมูลครูได้' });
    }
  });

  app.get('/api/students', async (req, res) => {
    try {
      const [rows] = await pool.query('SELECT * FROM students ORDER BY student_code ASC');
      res.json(rows);
    } catch (error) {
      console.error('Database Error:', error);
      res.status(500).json({ error: 'ไม่สามารถดึงข้อมูลนักเรียนได้' });
    }
  });

  app.post('/api/students', async (req, res) => {
    const { name, student_code, level, school_id } = req.body;
    try {
      await pool.query('INSERT INTO students (name, student_code, level, school_id) VALUES (?, ?, ?, ?)', 
        [name, student_code, level, school_id]);
      res.json({ status: 'success', message: 'เพิ่มนักเรียนเรียบร้อย' });
    } catch (error) {
      console.error('Database Error:', error);
      res.status(500).json({ error: 'ไม่สามารถเพิ่มข้อมูลนักเรียนได้' });
    }
  });

  app.post('/api/students/promote', async (req, res) => {
    const { studentIds, nextLevel } = req.body;
    try {
      await pool.query('UPDATE students SET level = ? WHERE id IN (?)', [nextLevel, studentIds]);
      res.json({ status: 'success', message: `เลื่อนชั้นนักเรียนเป็น ${nextLevel} เรียบร้อย` });
    } catch (error) {
      console.error('Database Error:', error);
      res.status(500).json({ error: 'ไม่สามารถเลื่อนชั้นนักเรียนได้' });
    }
  });

  app.post('/api/save-record', async (req, res) => {
    const { studentId, subjectCode, subjectName, k, p, a, midterm, final, year, semester } = req.body;
    try {
      await pool.query(
        'INSERT INTO grades (student_id, subject_code, subject_name, k_score, p_score, a_score, midterm_score, final_score, academic_year, semester) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [studentId, subjectCode, subjectName, k, p, a, midterm, final, year, semester]
      );
      res.json({ status: 'success' });
    } catch (error) {
      console.error('Database Error:', error);
      res.status(500).json({ error: 'ไม่สามารถบันทึกคะแนนได้' });
    }
  });

  app.get('/api/export-pdf/:studentId', async (req, res) => {
    const { studentId } = req.params;
    try {
      const PDFDocument = (await import('pdfkit')).default;
      const doc = new PDFDocument();
      res.setHeader('Content-Type', 'application/pdf');
      res.setHeader('Content-Disposition', `attachment; filename=PAPOR5_${studentId}.pdf`);
      doc.pipe(res);
      doc.fontSize(20).text('แบบรายงานผลการพัฒนาคุณภาพผู้เรียน (ปพ.5)', { align: 'center' });
      doc.moveDown();
      doc.fontSize(14).text(`รหัสนักเรียน: ${studentId}`);
      doc.text('วิชา: ภาษาไทย (ท11101)');
      doc.text('ผลการเรียน: 4.0');
      doc.end();
    } catch (err) {
      res.status(500).send('Error generating PDF');
    }
  });

  // --- Vite Middleware for Development ---
  // Force production mode if on Plesk or if NODE_ENV is production
  const isProduction = process.env.NODE_ENV === 'production' || process.env.PLESK_REVISION || process.env.IISNODE_VERSION;
  
  if (!isProduction) {
    try {
      const { createServer: createViteServer } = await import('vite');
      const vite = await createViteServer({
        server: { middlewareMode: true },
        appType: 'spa',
      });
      app.use(vite.middlewares);
      console.log('Vite middleware enabled (Development Mode)');
    } catch (e) {
      console.warn('Vite not found or failed to start, falling back to static mode.');
      serveStatic();
    }
  } else {
    console.log('Production Mode: Serving Static Files');
    serveStatic();
  }

  function serveStatic() {
    const distPath = path.join(__dirname, 'dist');
    const indexPath = path.join(distPath, 'index.html');
    
    console.log(`Serving static files from: ${distPath}`);
    
    // Check if dist/index.html exists
    import('fs').then(fs => {
      if (fs.existsSync(indexPath)) {
        console.log('Found index.html in dist folder.');
      } else {
        console.error('CRITICAL: index.html NOT FOUND in dist folder! Did you run "npm run build"?');
      }
    });

    app.use(express.static(distPath));
    app.get('*', (req, res, next) => {
      if (req.path.startsWith('/api')) {
        return next();
      }
      res.sendFile(indexPath);
    });
  }

  app.listen(PORT, () => {
    console.log(`Server running at http://localhost:${PORT}`);
  });
}

startServer();
