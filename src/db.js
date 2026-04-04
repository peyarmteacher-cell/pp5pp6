import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

console.log('--- DATABASE CONFIGURATION ---');
console.log('Host:', process.env.DB_HOST);
console.log('User:', process.env.DB_USER);
console.log('Database:', process.env.DB_NAME);
console.log('Port:', process.env.DB_PORT || 3306);

// Create the connection pool with more robust settings
const pool = mysql.createPool({
  host: process.env.DB_HOST || '127.0.0.1',
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  port: parseInt(process.env.DB_PORT || '3306'),
  waitForConnections: true,
  connectionLimit: 5, // ลดจำนวนลงเพื่อความเสถียรบน Shared Hosting
  queueLimit: 0,
  connectTimeout: 10000, // 10 วินาที
  enableKeepAlive: true,
  keepAliveInitialDelay: 0
});

export default pool;
