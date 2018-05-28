const path = require('path');
const WebpackAssetsManifest = require('webpack-assets-manifest');
const polyfills_for_fetch = require('../../../../../tools/utils/scripts/ie11-polyfill-names.js')
    .polyfills_for_fetch;
const webpack_configurator = require('../../../../../tools/utils/scripts/webpack-configurator.js');

const webpack_config = {
    entry: {
        'en_US.min': polyfills_for_fetch.concat([
            'dom4',
            './src/index.en_US.js'
        ]),
        'fr_FR.min': polyfills_for_fetch.concat([
            'dom4',
            './src/index.fr_FR.js'
        ])
    },
    context: path.resolve(__dirname),
    output: {
        path: path.resolve(__dirname, 'dist/'),
        filename: 'tlp-[chunkhash].[name].js',
        library: 'tlp'
    },
    resolve: {
        alias: {
            select2: 'select2/dist/js/select2.full.js'
        }
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(
                webpack_configurator.babel_options_ie11
            )
        ]
    },
    plugins: [
        new WebpackAssetsManifest({
            output: 'manifest.json',
            merge: true,
            writeToDisk: true,
            customize(entry) {
                return {
                    key: `tlp.${entry.key}`,
                    value: entry.value
                };
            }
        })
    ]
};

module.exports = webpack_config;
