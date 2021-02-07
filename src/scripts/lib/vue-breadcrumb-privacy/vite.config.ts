import { defineConfig } from "vite";
import { createVuePlugin } from "vite-plugin-vue2";
import * as path from "path";

export default defineConfig({
    plugins: [createVuePlugin()],
    build: {
        brotliSize: false,
        lib: {
            entry: path.resolve(__dirname, "src/index.ts"),
            name: "BreadcrumbPrivacy",
        },
        rollupOptions: {
            external: ["vue", "@tuleap/tlp"],
            output: {
                globals: {
                    vue: "Vue",
                    "@tuleap/tlp": "tlp",
                },
            },
        },
    },
});
