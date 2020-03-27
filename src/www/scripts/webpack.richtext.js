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

const path = require("path");
const webpack_configurator = require("../../../tools/utils/scripts/webpack-configurator.js");

const assets_dir_path = path.resolve(__dirname, "../assets");
const public_assets_path = "/assets/";

module.exports = {
    entry: {
        "rich-text-editor": "./tuleap/textarea_rte.js",
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path, public_assets_path),
    externals: {
        ckeditor: "CKEDITOR",
        tuleap: "tuleap",
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files,
        ],
    },
    resolve: {
        alias: {
            "tlp-fetch": path.resolve(__dirname, "../themes/common/tlp/src/js/fetch-wrapper.js"),
        },
    },
    optimization: {
        // Prototype doesn't like minimization due to the fact
        // that it checks for the presence of "$super" argument
        // during class initialization.
        minimize: false,
    },
};
