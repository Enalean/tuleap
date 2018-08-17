/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

const BabelPresetEnv = require("babel-preset-env");
const BabelPluginObjectRestSpread = require("babel-plugin-transform-object-rest-spread");
const BabelPluginSyntaxDynamicImport = require("babel-plugin-syntax-dynamic-import");
const BabelPluginRewireExports = require("babel-plugin-rewire-exports").default;
const BabelPluginIstanbul = require("babel-plugin-istanbul").default;

const babel_preset_env_ie_config = [
    BabelPresetEnv,
    {
        targets: {
            ie: 11
        },
        modules: false
    }
];

const babel_preset_env_chrome_config = [
    BabelPresetEnv,
    {
        targets: {
            browsers: ["last 2 Chrome versions"]
        },
        modules: false,
        useBuiltIns: true,
        shippedProposals: true
    }
];

const babel_options_ie11 = {
    presets: [babel_preset_env_ie_config],
    plugins: [BabelPluginObjectRestSpread, BabelPluginSyntaxDynamicImport]
};

const babel_options_karma = {
    env: {
        watch: babel_options_ie11,
        production: babel_options_ie11,
        test: {
            presets: [babel_preset_env_chrome_config],
            plugins: [
                BabelPluginObjectRestSpread,
                BabelPluginSyntaxDynamicImport,
                BabelPluginRewireExports
            ]
        },
        coverage: {
            presets: [babel_preset_env_chrome_config],
            plugins: [
                BabelPluginObjectRestSpread,
                BabelPluginSyntaxDynamicImport,
                BabelPluginRewireExports,
                [
                    BabelPluginIstanbul,
                    {
                        exclude: ["**/*.spec.js"]
                    }
                ]
            ]
        }
    }
};

function configureBabelRule(babel_options) {
    return {
        test: /\.js$/,
        exclude: [/node_modules/, /vendor/, /bower_components/],
        use: [
            {
                loader: "babel-loader",
                options: babel_options
            }
        ]
    };
}

const rule_vue_loader = {
    test: /\.vue$/,
    exclude: /node_modules/,
    use: [{ loader: "vue-loader" }]
};

const rule_po_files = {
    test: /\.po$/,
    exclude: /node_modules/,
    use: [{ loader: "json-loader" }, { loader: "po-gettext-loader" }]
};

const rule_mustache_files = {
    test: /\.mustache$/,
    exclude: /node_modules/,
    use: { loader: "raw-loader" }
};

const rule_ng_cache_loader = {
    test: /\.html$/,
    exclude: [/node_modules/, /vendor/, /bower_components/],
    use: [
        {
            loader: "ng-cache-loader",
            query: "-url"
        }
    ]
};

const rule_angular_gettext_loader = {
    test: /\.po$/,
    exclude: [/node_modules/, /vendor/, /bower_components/],
    use: [
        {
            loader: "angular-gettext-loader",
            query: "browserify=true"
        }
    ]
};

const rule_angular_gettext_extract_loader = {
    test: /src.*\.(js|html)$/,
    exclude: [/node_modules/, /vendor/, /bower_components/],
    use: [
        {
            loader: "angular-gettext-extract-loader",
            query: "pofile=po/template.pot"
        }
    ]
};

const rule_easygettext_loader = {
    test: /\.po$/,
    exclude: /node_modules/,
    use: [{ loader: "json-loader" }, { loader: "easygettext-loader" }]
};

module.exports = {
    configureBabelRule,
    babel_options_ie11,
    babel_options_karma,
    rule_po_files,
    rule_mustache_files,
    rule_vue_loader,
    rule_ng_cache_loader,
    rule_angular_gettext_loader,
    rule_angular_gettext_extract_loader,
    rule_easygettext_loader
};
