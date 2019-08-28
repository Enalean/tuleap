const path = require("path");
const webpack_configurator = require("../../../tools/utils/scripts/webpack-configurator.js");

const assets_dir_path = path.resolve(__dirname, "../../../src/www/assets/crosstracker/scripts");

const webpack_config = {
    entry: {
        "cross-tracker": "./cross-tracker/src/index.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: "tlp"
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader
        ]
    },
    plugins: [
        webpack_configurator.getManifestPlugin(),
        webpack_configurator.getVueLoaderPlugin(),
        webpack_configurator.getMomentLocalePlugin()
    ],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias
    }
};

if (process.env.NODE_ENV === "test" || process.env.NODE_ENV === "watch") {
    webpack_config.devtool = "cheap-eval-source-map";
}

module.exports = webpack_config;
