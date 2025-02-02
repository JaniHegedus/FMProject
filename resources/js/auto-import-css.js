// auto-import-css.js

// Import all CSS files under the current directory (and subdirectories) eagerly.
// auto-import-css.js
const cssModules = import.meta.glob('/resources/css/**/*.css', { eager: true });

// No further action is needed here—importing the files is enough for Vite to process and inject the styles.
