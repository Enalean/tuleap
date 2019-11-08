const path = require("path");
const webpack_configurator = require("../../../../tools/utils/scripts/webpack-configurator.js");

const assets_dir_path = path.resolve(__dirname, "../assets");
const manifest_plugin = webpack_configurator.getManifestPlugin();

const webpack_config_for_charts = {
    entry: {
        "burnup-chart": "./burnup-chart/src/burnup-chart.js",
        "home-burndowns": "./home.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tuleap: "tuleap",
        jquery: "jQuery"
    },
    resolve: {
        alias: {
            "charts-builders": path.resolve(
                __dirname,
                "../../../../src/www/scripts/charts-builders/"
            ),
            "d3-array$": path.resolve(__dirname, "node_modules/d3-array"),
            "d3-scale$": path.resolve(__dirname, "node_modules/d3-scale"),
            "d3-axis$": path.resolve(__dirname, "node_modules/d3-axis")
        }
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files
        ]
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()]
};

const path_to_badge = path.resolve(
    __dirname,
    "../../../../src/www/scripts/project/admin/permissions-per-group/"
);

const webpack_config_for_overview_and_vue = {
    entry: {
        "scrum-header": "./scrum-header.js",
        "permission-per-group": "./permissions-per-group/src/index.js",
        administration: "./administration.js",
        "planning-admin": "./planning-admin.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: "tlp"
    },
    resolve: {
        alias: {
            "permission-badge": path_to_badge
        }
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader
        ]
    },
    plugins: [manifest_plugin, webpack_configurator.getVueLoaderPlugin()],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias
    }
};

const webpack_config_for_artifact_additional_action = {
    entry: {
        "artifact-additional-action": "./artifact-additional-action/src/index.ts"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(
        assets_dir_path,
        "/plugins/agiledashboard/assets/"
    ),
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files
        ]
    },
    plugins: [manifest_plugin, webpack_configurator.getTypescriptCheckerPlugin(false)],
    resolve: {
        extensions: [".ts", ".js"]
    }
};

module.exports = [
    webpack_config_for_charts,
    webpack_config_for_overview_and_vue,
    webpack_config_for_artifact_additional_action
];
