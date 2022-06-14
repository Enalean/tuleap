/*
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

const path = require("path");
const { webpack_configurator } = require("@tuleap/build-system-configurator");

const rule_vue_loader_with_css_extraction = {
    ...webpack_configurator.rule_vue_loader,
    use: [
        {
            loader: "vue-loader",
            options: {
                CSSExtract: true,
            },
        },
    ],
};

module.exports = [
    {
        entry: {
            "artifact-create-branch": "./scripts/artifact-create-branch-action/src/index.ts",
        },
        context: path.resolve(__dirname),
        output: webpack_configurator.configureOutput(path.resolve(__dirname, "./frontend-assets/")),
        externals: {
            codendi: "codendi",
        },
        module: {
            rules: [
                ...webpack_configurator.configureTypescriptRules(),
                webpack_configurator.rule_easygettext_loader,
                rule_vue_loader_with_css_extraction,
                webpack_configurator.rule_scss_loader,
                webpack_configurator.rule_css_assets,
            ],
        },
        resolve: {
            extensions: [".ts", ".js", ".vue"],
            alias: {
                "@vue/composition-api": path.resolve(
                    __dirname,
                    "node_modules",
                    "@vue",
                    "composition-api"
                ),
            },
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            webpack_configurator.getVueLoaderPlugin(),
            webpack_configurator.getTypescriptCheckerPlugin(true),
            require("unplugin-vue2-script-setup/webpack")(),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
        resolveLoader: {
            alias: webpack_configurator.easygettext_loader_alias,
        },
    },
];
