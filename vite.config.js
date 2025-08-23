import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import html from "@rollup/plugin-html";
import { glob } from "glob";

/**
 * Get Files from a directory
 * @param {string} query
 * @returns array
 */
function GetFilesArray(query) {
    return glob.sync(query);
}

/**
 * Js Files
 */
// Page JS Files
const pageJsFiles = GetFilesArray("resources/assets/js/*.js");

// Processing Vendor JS Files
const vendorJsFiles = GetFilesArray("resources/assets/vendor/js/*.js");

// Processing Libs JS Files
const LibsJsFiles = GetFilesArray("resources/assets/vendor/libs/**/*.js");

/**
 * Scss Files
 */
// Processing Core, Themes & Pages Scss Files
const CoreScssFiles = GetFilesArray(
    "resources/assets/vendor/scss/**/!(_)*.scss",
);

// Processing Libs Scss & Css Files
const LibsScssFiles = GetFilesArray(
    "resources/assets/vendor/libs/**/!(_)*.scss",
);
const LibsCssFiles = GetFilesArray("resources/assets/vendor/libs/**/*.css");

// Processing Fonts Scss Files
const FontsScssFiles = GetFilesArray(
    "resources/assets/vendor/fonts/!(_)*.scss",
);

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/assets/css/demo.css",
                "resources/js/app.js",
                "resources/assets/css/banking.css",
                "resources/assets/css/refunds-datatable.css",
                ...pageJsFiles,
                ...vendorJsFiles,
                ...LibsJsFiles,
                ...CoreScssFiles,
                ...LibsScssFiles,
                ...LibsCssFiles,
                ...FontsScssFiles,
            ],
            refresh: true,
        }),
        html(),
    ],
    server: {
        host: "0.0.0.0",
        port: 5173,
        strictPort: true,
        hmr: {
            host: "localhost",
            port: 5173,
            protocol: "ws",
        },
        watch: {
            usePolling: true,
            interval: 1000,
        },
        cors: {
            origin: ["http://localhost:8000", "http://127.0.0.1:8000"],
            credentials: true,
            methods: ["GET", "POST", "PUT", "DELETE", "OPTIONS"],
            allowedHeaders: [
                "Content-Type",
                "Authorization",
                "X-Requested-With",
            ],
        },
        origin: "http://localhost:5173",
    },
    build: {
        manifest: true,
        outDir: "public/build",
        // REMOVIDO: rollupOptions que estava sobrescrevendo os inputs do laravel()
        emptyOutDir: true,
    },
});
