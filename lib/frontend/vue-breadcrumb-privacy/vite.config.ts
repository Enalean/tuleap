/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { vite } from "@tuleap/build-system-configurator";
import { createVuePlugin } from "vite-plugin-vue2";
import * as path from "path";
import dts from "vite-dts";

export default vite.defineLibConfig({
    plugins: [createVuePlugin(), dts()],
    build: {
        lib: {
            entry: path.resolve(__dirname, "src/index.ts"),
            name: "BreadcrumbPrivacy",
        },
        rollupOptions: {
            external: ["vue", "@tuleap/tlp-popovers", "@tuleap/project-privacy-helper"],
            output: {
                globals: {
                    vue: "Vue",
                    "@tuleap/tlp-popovers": "tlp",
                    "@tuleap/project-privacy-helper": "@tuleap/project-privacy-helper",
                },
            },
        },
    },
});
