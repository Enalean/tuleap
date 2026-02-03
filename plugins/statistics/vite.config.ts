/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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
import * as path from "node:path";
import { viteExternalsPlugin } from "vite-plugin-externals";

export default vite.defineAppConfig(
    {
        plugin_name: path.basename(__dirname),
    },
    {
        plugins: [viteExternalsPlugin({ tlp: "tlp", ckeditor4: "CKEDITOR", tuleap: "tuleap" })],
        build: {
            rollupOptions: {
                input: {
                    "disk-usage": path.resolve(
                        __dirname,
                        "themes/BurningParrot/css/disk-usage.scss",
                    ),
                    "disk-usage-pie": path.resolve(
                        __dirname,
                        "scripts/disk-usage-pie/src/disk-usage-pie-chart.js",
                    ),
                    admin: path.resolve(__dirname, "scripts/admin.js"),
                    "style-bp": path.resolve(__dirname, "themes/BurningParrot/css/statistics.scss"),
                },
            },
        },
    },
);
