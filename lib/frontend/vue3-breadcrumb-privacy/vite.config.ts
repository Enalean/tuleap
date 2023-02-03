/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { vite, viteDtsPlugin } from "@tuleap/build-system-configurator";
import vue from "@vitejs/plugin-vue";
import * as path from "path";

export default vite.defineLibConfig({
    build: {
        lib: {
            entry: path.resolve(__dirname, "src/index.ts"),
            name: "TuleapVue3BreadcrumbPrivacy",
        },
        rollupOptions: {
            external: [
                "vue",
                "@vueuse/core",
                "@tuleap/tlp-popovers",
                "@tuleap/project-privacy-helper",
            ],
            output: {
                globals: {
                    vue: "Vue",
                    "@vueuse/core": "VueUse",
                    "@tuleap/tlp-popovers": "TlpPopovers",
                    "@tuleap/project-privacy-helper": "ProjectPrivacyHelper",
                },
            },
        },
    },
    plugins: [vue({ customElement: true }), viteDtsPlugin()],
});
