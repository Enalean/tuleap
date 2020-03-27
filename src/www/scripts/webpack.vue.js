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

module.exports = {
    entry: {
        "news-permissions": "./news/permissions-per-group/index.js",
        "frs-permissions": "./frs/permissions-per-group/index.js",
        "project-admin-services": "./project/admin/services/src/index-project-admin.js",
        "site-admin-services": "./project/admin/services/src/index-site-admin.js",
        "project-admin-banner": "./project/admin/banner/index-banner-project-admin.ts",
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path, "/assets/"),
    externals: {
        tlp: "tlp",
        ckeditor: "CKEDITOR",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader,
        ],
    },
    plugins: [
        webpack_configurator.getVueLoaderPlugin(),
        webpack_configurator.getTypescriptCheckerPlugin(true),
    ],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias,
    },
    resolve: {
        extensions: [".js", ".ts", ".vue"],
    },
};
