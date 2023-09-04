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
const { webpack_configurator } = require("@tuleap/build-system-configurator");
const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "./frontend-assets/"),
    "/assets/testmanagement/",
);
const manifest_plugin = webpack_configurator.getManifestPlugin();

const webpack_config_for_angular = {
    entry: {
        testmanagement: "./scripts/testmanagement/src/app.js",
        "testmanagement-admin": "./scripts/testmanagement/src/admin/testmanagement-admin.ts",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
        jquery: "jQuery",
        ckeditor4: "CKEDITOR",
    },
    resolve: {
        alias: {
            // angular alias for the artifact modal (otherwise it is included twice)
            angular$: path.resolve(__dirname, "./node_modules/angular"),
            "angular-sanitize$": path.resolve(__dirname, "./node_modules/angular-sanitize"),
            docx: path.resolve(__dirname, "node_modules", "docx"),
        },
        extensions: [".ts", ".js"],
        fallback: {
            path: require.resolve("path-browserify"),
        },
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_ng_cache_loader,
            webpack_configurator.rule_angular_gettext_loader,
        ],
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()],
};

const webpack_config_for_vue_components = {
    entry: {
        "step-definition-field": "./scripts/step-definition-field/index.js",
    },
    context,
    output,
    externals: {
        jquery: "jQuery",
        ckeditor4: "CKEDITOR",
    },
    module: {
        rules: [
            webpack_configurator.rule_vue_images,
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader,
        ],
    },
    plugins: [manifest_plugin, webpack_configurator.getVueLoaderPlugin()],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias,
    },
};

const webpack_config_for_themes = {
    entry: {
        flamingparrot: "./themes/FlamingParrot/css/style.scss",
        "testmanagement-style": "./themes/BurningParrot/css/testmanagement.scss",
    },
    context,
    output,
    module: {
        rules: [webpack_configurator.rule_scss_loader, webpack_configurator.rule_css_assets],
    },
    plugins: [manifest_plugin, ...webpack_configurator.getCSSExtractionPlugins()],
};

module.exports = [
    webpack_config_for_angular,
    webpack_config_for_vue_components,
    webpack_config_for_themes,
];
