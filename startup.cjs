// Startup bridge for iisnode to support ES Modules
(async () => {
    try {
        await import('./server.js');
    } catch (err) {
        console.error('Failed to load application:', err);
        process.exit(1);
    }
})();
