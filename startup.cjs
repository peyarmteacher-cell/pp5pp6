const fs = require('fs');
const path = require('path');

const logFile = path.join(__dirname, 'startup-debug.log');
function log(msg) {
    const timestamp = new Date().toISOString();
    fs.appendFileSync(logFile, `[${timestamp}] ${msg}\n`);
}

log('--- STARTUP.CJS INITIATED ---');
log('Node Version: ' + process.version);
log('Current Directory: ' + __dirname);

// Startup bridge for iisnode to support ES Modules
(async () => {
    try {
        log('Attempting to import server.js...');
        await import('./server.js');
        log('server.js imported successfully.');
    } catch (err) {
        log('CRITICAL ERROR during startup: ' + err.stack);
        console.error('CRITICAL ERROR during startup:', err);
        process.exit(1);
    }
})();
