const path = require("path");
const webpack_configurator = require("../../../../../tools/utils/scripts/webpack-configurator.js");

const assets_dir_path = path.resolve(__dirname, "./dist");

const webpack_config = {
    entry: {
        "planning-v2": "./src/app/app.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: "tlp",
        jquery: "jQuery"
    },
    resolve: {
        alias: webpack_configurator.extendAliases(
            {
                "card-fields": path.resolve(__dirname, "../card-fields"),
                // angular-tlp
                angular$: path.resolve(__dirname, "node_modules/angular"),
                "angular-mocks$": path.resolve(__dirname, "node_modules/angular-mocks"),
                // card-fields dependencies
                "angular-sanitize$": path.resolve(__dirname, "node_modules/angular-sanitize"),
                moment$: path.resolve(__dirname, "node_modules/moment"),
                he$: path.resolve(__dirname, "node_modules/he"),
                striptags$: path.resolve(__dirname, "node_modules/striptags"),
                "escape-string-regexp$": path.resolve(
                    __dirname,
                    "node_modules/escape-string-regexp"
                )
            },
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
    plugins: [
        webpack_configurator.getManifestPlugin(),
        webpack_configurator.getMomentLocalePlugin()
    ]
};

module.exports = webpack_config;
