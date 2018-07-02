const path = require("path");
const webpack_configurator = require("../../../../../tools/utils/scripts/webpack-configurator.js");

const assets_dir_path = path.resolve(__dirname, "./dist");
const path_to_tlp = path.resolve(__dirname, "../../../../../src/www/themes/common/tlp/");
const manifest_plugin = webpack_configurator.getManifestPlugin();

const webpack_config_for_kanban = {
    entry: {
        kanban: "./src/app/app.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: "tlp",
        angular: "angular",
        jquery: "jQuery"
    },
    resolve: {
        alias: webpack_configurator.extendAliases(
            webpack_configurator.angular_artifact_modal_aliases,
            {
                "cumulative-flow-diagram": path.resolve(
                    __dirname,
                    "../cumulative-flow-diagram/index.js"
                ),
                "card-fields": path.resolve(__dirname, "../card-fields"),
                "angular-tlp": path.join(path_to_tlp, "angular-tlp"),
                // cumulative-flow-chart
                d3$: path.resolve(__dirname, "node_modules/d3"),
                lodash$: path.resolve(__dirname, "node_modules/lodash"),
                moment$: path.resolve(__dirname, "node_modules/moment"),
                // angular-tlp
                angular$: path.resolve(__dirname, "node_modules/angular"),
                "angular-mocks$": path.resolve(__dirname, "node_modules/angular-mocks"),
                // card-fields dependencies
                "angular-sanitize$": path.resolve(__dirname, "node_modules/angular-sanitize"),
                he$: path.resolve(__dirname, "node_modules/he"),
                striptags$: path.resolve(__dirname, "node_modules/striptags"),
                "escape-string-regexp$": path.resolve(
                    __dirname,
                    "node_modules/escape-string-regexp"
                )
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
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()]
};

const webpack_config_for_angular = {
    entry: {
        angular: "angular"
    },
    output: webpack_configurator.configureOutput(assets_dir_path),
    plugins: [manifest_plugin]
};

module.exports = [webpack_config_for_kanban, webpack_config_for_angular];
