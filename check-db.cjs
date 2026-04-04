const mysql = require('mysql2/promise');
const dotenv = require('dotenv');
const fs = require('fs');
const path = require('path');

dotenv.config();

const logFile = path.join(__dirname, 'db-test-result.log');
function log(msg) {
    const timestamp = new Date().toISOString();
    fs.appendFileSync(logFile, `[${timestamp}] ${msg}\n`);
    console.log(msg);
}

async function testConnection() {
    log('--- STARTING DATABASE CONNECTION TEST ---');
    log(`Host: ${process.env.DB_HOST}`);
    log(`User: ${process.env.DB_USER}`);
    log(`Database: ${process.env.DB_NAME}`);
    log(`Port: ${process.env.DB_PORT || 3306}`);

    try {
        const connection = await mysql.createConnection({
            host: process.env.DB_HOST || '127.0.0.1',
            user: process.env.DB_USER,
            password: process.env.DB_PASSWORD,
            database: process.env.DB_NAME,
            port: parseInt(process.env.DB_PORT || '3306'),
            connectTimeout: 10000
        });

        log('✅ SUCCESS: Database connected successfully!');
        const [rows] = await connection.execute('SELECT 1 + 1 AS result');
        log(`✅ QUERY TEST: 1 + 1 = ${rows[0].result}`);
        
        // เช็คว่ามีตาราง schools ไหม
        const [tables] = await connection.execute('SHOW TABLES');
        log(`✅ TABLES FOUND: ${tables.length}`);
        
        await connection.end();
        log('--- TEST COMPLETED SUCCESSFULLY ---');
    } catch (err) {
        log(`❌ ERROR: Connection failed!`);
        log(`❌ ERROR MESSAGE: ${err.message}`);
        log(`❌ ERROR CODE: ${err.code}`);
        log(`❌ ERROR STACK: ${err.stack}`);
        log('--- TEST FAILED ---');
    }
}

testConnection();
