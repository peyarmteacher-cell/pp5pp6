console.log('--- STARTUP.CJS INITIATED ---');
// Startup bridge for iisnode to support ES Modules
(async () => {
    try {
        console.log('Attempting to import server.js...');
        await import('./server.js');
        console.log('server.js imported successfully.');
    } catch (err) {
        console.error('CRITICAL ERROR during startup:', err);
        process.exit(1);
    }
})();
