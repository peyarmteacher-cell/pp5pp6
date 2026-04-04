import express from 'express';
import path from 'path';
import { fileURLToPath } from 'url';
import { createServer as createViteServer } from 'vite';
import cors from 'cors';
import dotenv from 'dotenv';

import pool from './src/db.js';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function startServer() {
  const app = express();
  const PORT = process.env.PORT || 3000;

  app.use(cors());
  app.use(express.json({ limit: '50mb' }));
  app.use(express.urlencoded({ extended: true, limit: '50mb' }));

  // --- API Routes with MySQL ---
  
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
  const isProduction = process.env.NODE_ENV === 'production' || process.env.PLESK_REVISION;
  
  if (!isProduction) {
    try {
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

  app.listen(PORT, '0.0.0.0', () => {
    console.log(`Server running at http://localhost:${PORT}`);
  });
}

startServer();
