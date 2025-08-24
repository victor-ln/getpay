import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { glob } from "glob";

/**
 * Get Files from a directory
 * @param {string} query
 * @returns array
 */
function GetFilesArray(query) {
    return glob.sync(query);
}

// Todos os seus arquivos de entrada
const allInputFiles = [
    // Arquivos principais
    "resources/css/app.css",
    "resources/js/app.js",

    // Outros CSS
    "resources/assets/css/demo.css",
    "resources/assets/css/banking.css",
    "resources/assets/css/refunds-datatable.css",

    // Arquivos JS (páginas, vendors, libs)
    ...GetFilesArray("resources/assets/js/*.js"),
    ...GetFilesArray("resources/assets/vendor/js/*.js"),
    ...GetFilesArray("resources/assets/vendor/libs/**/*.js"),

    // Arquivos SCSS e CSS (core, libs, fonts)
    ...GetFilesArray("resources/assets/vendor/scss/**/!(_)*.scss"),
    ...GetFilesArray("resources/assets/vendor/libs/**/!(_)*.scss"),
    ...GetFilesArray("resources/assets/vendor/libs/**/*.css"),
    ...GetFilesArray("resources/assets/vendor/fonts/!(_)*.scss"),
];

export default defineConfig({
    plugins: [
        laravel({
            // Apenas a lista de input é necessária aqui
            input: allInputFiles,
            refresh: true,
        }),
    ],
    // A configuração do servidor de desenvolvimento pode ser mantida se você a utiliza
    server: {
        host: "0.0.0.0",
        hmr: {
            host: "localhost",
        },
        watch: {
            usePolling: true,
        },
    },
});
