/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import * as path from "node:path";
import { vite, viteDtsPlugin } from "@tuleap/build-system-configurator";
import Replace from "@rollup/plugin-replace";

export default vite.defineLibConfig({
    plugins: [
        // Remove IE hack in distributed flatpickr CSS
        Replace({
            include: ["styles/main.scss"],
            values: { "7ch\\0": "6ch" },
            preventAssignment: true,
        }),
        viteDtsPlugin(),
    ],
    build: {
        lib: {
            name: "TlpDatePicker",
            entry: path.resolve(__dirname, "src/main.ts"),
        },
    },
});
