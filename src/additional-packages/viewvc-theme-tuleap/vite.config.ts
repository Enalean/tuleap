import { defineConfig } from 'vite';
import * as path from "path";

export default defineConfig({
    build: {
        rollupOptions: {
            input: {
                style: path.resolve(__dirname, "src/assets/style.scss"),
            },
            output: {
                assetFileNames: '[name].[ext]'
            }
        },
        outDir: path.resolve(__dirname, "src/assets/"),
        emptyOutDir: false,
    },
});
