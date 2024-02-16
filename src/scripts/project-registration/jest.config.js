/*
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

import { env } from "node:process";
import { defineJestConfiguration } from "@tuleap/build-system-configurator";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

env.DISABLE_TS_TYPECHECK = "true";

const jest_base_config = defineJestConfiguration();
export default {
    displayName: "project-registration",
    ...jest_base_config,
    transform: {
        ...jest_base_config.transform,
        "^.+\\.vue$": "unplugin-vue2-script-setup/jest",
    },
    moduleNameMapper: {
        ...jest_base_config.moduleNameMapper,
        "^vue$": path.resolve(__dirname, "./node_modules/vue/"),
    },
};
