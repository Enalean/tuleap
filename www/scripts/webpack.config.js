const path = require("path");
const webpack_configurator = require("../../../../tools/utils/scripts/webpack-configurator.js");

const assets_dir_path = path.resolve(__dirname, "../assets");
const manifest_plugin = webpack_configurator.getManifestPlugin();

const webpack_config_for_angular = {
    entry: {
        testmanagement: "./angular/src/app/app.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: "tlp",
        jquery: "jQuery"
    },
    resolve: {
        modules: [
            // This ensures that dependencies resolve their imported modules in testmanagement's node_modules
            path.resolve(__dirname, "node_modules"),
            "node_modules"
        ],
        alias: webpack_configurator.extendAliases(
            {},
            webpack_configurator.angular_artifact_modal_aliases
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

const webpack_config_for_vue_components = {
    entry: {
        "step-definition-field": "./step-definition-field/index.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        codendi: "codendi"
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
        alias: webpack_configurator.extendAliases({}, webpack_configurator.easygettext_loader_alias)
    }
};

module.exports = [webpack_config_for_angular, webpack_config_for_vue_components];
