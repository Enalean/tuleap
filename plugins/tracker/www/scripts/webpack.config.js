const path = require("path");
const webpack_configurator = require("../../../../tools/utils/scripts/webpack-configurator.js");

const assets_dir_path = path.resolve(__dirname, "../assets");
const assets_public_path = "assets/";
const manifest_plugin = webpack_configurator.getManifestPlugin();

const webpack_config_for_artifact_modal = {
    entry: "./angular-artifact-modal/index.js",
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: "tlp"
    },
    resolve: {
        alias: webpack_configurator.extendAliases(
            webpack_configurator.angular_artifact_modal_aliases,
            {
                // Those are needed for tests
                angular$: path.resolve(__dirname, "node_modules/angular"),
                "angular-mocks$": path.resolve(__dirname, "node_modules/angular-mocks"),
                jquery$: path.resolve(__dirname, "node_modules/jquery")
            }
        )
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_karma),
            webpack_configurator.rule_ng_cache_loader,
            webpack_configurator.rule_angular_gettext_loader
        ]
    },
    plugins: [webpack_configurator.getMomentLocalePlugin()]
};

const webpack_config_for_burndown_chart = {
    entry: {
        "burndown-chart": "./burndown-chart/src/burndown-chart.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
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
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_karma),
            webpack_configurator.rule_po_files
        ]
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()]
};

const path_to_badge = path.resolve(
    __dirname,
    "../../../../src/www/scripts/project/admin/permissions-per-group/"
);

const webpack_config_for_vue = {
    entry: {
        "tracker-report-expert-mode": "./report/index.js",
        "tracker-permissions-per-group": "./permissions-per-group/src/index.js",
        "tracker-workflow-transitions": "./workflow-transitions/src/index.js",
        MoveArtifactModal: "./artifact-action-buttons/src/index.js",
        TrackerAdminFields: "./TrackerAdminFields.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path, assets_public_path),
    externals: {
        codendi: "codendi",
        jquery: "jQuery"
    },
    resolve: {
        alias: webpack_configurator.extendAliases(
            webpack_configurator.tlp_fetch_alias,
            webpack_configurator.tlp_mocks_alias,
            webpack_configurator.jquery_mocks_alias,
            {
                "permission-badge": path_to_badge
            }
        )
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_karma),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader
        ]
    },
    plugins: [manifest_plugin, webpack_configurator.getVueLoaderPlugin()],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias
    }
};

if (process.env.NODE_ENV === "watch" || process.env.NODE_ENV === "test") {
    webpack_config_for_artifact_modal.devtool = "cheap-module-eval-source-map";
    webpack_config_for_burndown_chart.devtool = "cheap-module-eval-source-map";
    webpack_config_for_vue.devtool = "cheap-module-eval-source-map";
}

if (process.env.NODE_ENV === "production") {
    module.exports = [webpack_config_for_burndown_chart, webpack_config_for_vue];
} else {
    module.exports = [
        webpack_config_for_artifact_modal,
        webpack_config_for_burndown_chart,
        webpack_config_for_vue
    ];
}
