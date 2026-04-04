const fs = require('fs');
const path = require('path');

const logFile = path.join(__dirname, 'startup-debug.log');
function log(msg) {
    const timestamp = new Date().toISOString();
    const formattedMsg = `[${timestamp}] ${msg}\n`;
    fs.appendFileSync(logFile, formattedMsg);
    console.log(msg);
}

log('--- STARTUP.CJS INITIATED ---');
log('Node Version: ' + process.version);
log('Current Directory: ' + __dirname);
log('Process ID: ' + process.pid);

// Global Error Handlers for the bridge itself
process.on('uncaughtException', (err) => {
    log('CRITICAL: Uncaught Exception in startup bridge: ' + err.stack);
    process.exit(1);
});

process.on('unhandledRejection', (reason, promise) => {
    log('CRITICAL: Unhandled Rejection in startup bridge: ' + reason);
    process.exit(1);
});

// Startup bridge for iisnode to support ES Modules
(async () => {
    try {
        log('Attempting to import server.js...');
        // Use an absolute path to be safe
        const serverPath = path.resolve(__dirname, 'server.js');
        log('Server path: ' + serverPath);
        
        if (!fs.existsSync(serverPath)) {
            throw new Error(`server.js not found at ${serverPath}`);
        }

        await import('file://' + serverPath);
        log('server.js imported successfully.');
    } catch (err) {
        log('CRITICAL ERROR during startup: ' + err.stack);
        console.error('CRITICAL ERROR during startup:', err);
        process.exit(1);
    }
})();
