/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import { fileURLToPath } from "node:url";
import { webpack_configurator } from "@tuleap/build-system-configurator";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const webpack_config_for_tlp_doc = {
    entry: {
        style: "./css/main.scss",
        script: "./src/index.js",
    },
    context: __dirname,
    // This one does NOT go in ./frontend-assets because we do not deliver it in production, only in dev environment
    output: webpack_configurator.configureOutput(
        path.resolve(__dirname, "../../www/tlp-doc/dist/"),
        "/tlp-doc/dist/"
    ),
    externals: {
        tlp: "tlp",
    },
    resolve: {
        extensions: [".js", ".ts"],
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_scss_loader,
        ],
    },
    plugins: [
        webpack_configurator.getCleanWebpackPlugin(),
        webpack_configurator.getManifestPlugin(),
        ...webpack_configurator.getCSSExtractionPlugins(),
    ],
};

export default [webpack_config_for_tlp_doc];
