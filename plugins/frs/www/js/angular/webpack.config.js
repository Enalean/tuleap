const path = require("path");
const webpack_configurator = require("../../../../../tools/utils/scripts/webpack-configurator.js");

const assets_dir_path = path.resolve(__dirname, "../../assets");

const webpack_config = {
    entry: {
        "tuleap-frs": "./src/app/app.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    resolve: {
        alias: {
            "angular-ui-bootstrap-templates": path.resolve(
                __dirname,
                "vendor/angular-ui-bootstrap-bower/ui-bootstrap-tpls.js"
            ),
            // Shorthand for testing purpose
            "tuleap-frs-module": path.resolve(__dirname, "./src/app/app.js")
        }
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_karma),
            webpack_configurator.rule_ng_cache_loader,
            webpack_configurator.rule_angular_gettext_loader
        ]
    },
    plugins: [webpack_configurator.getManifestPlugin()]
};

if (process.env.NODE_ENV === "watch") {
    webpack_config.plugins.push(webpack_configurator.getAngularGettextPlugin());
}

module.exports = webpack_config;
