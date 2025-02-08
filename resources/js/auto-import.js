// auto-import.js

// Glob all .js files eagerly.
const modules = import.meta.glob('./**/*.js', { eager: true });

// Loop through the modules, skipping this file (auto-import.js)
Object.entries(modules).forEach(([path, module]) => {
    if (path.includes('auto-import.js')) return; // Skip self-import
    if (path.includes('bootstrap.js')) return; // Skip self-import
    if (typeof module.init === 'function') {
        module.init();
    }
}); //NIGGA
