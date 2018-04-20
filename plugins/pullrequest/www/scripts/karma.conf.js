const path               = require('path');
const webpack_config     = require('./webpack.config.js');
const karma_configurator = require('../../../../tools/utils/scripts/karma-configurator.js');

webpack_config.mode = 'development';

module.exports = function(config) {
    const coverage_dir = path.resolve(__dirname, './coverage');
    const base_config  = karma_configurator.setupBaseKarmaConfig(
        config,
        webpack_config,
        coverage_dir
    );

    Object.assign(base_config, {
        files  : [
            karma_configurator.jasmine_promise_matchers_path,
            'node_modules/jquery/dist/jquery.js',
            'src/app/app.spec.js'
        ],
        preprocessors: {
            'src/app/app.spec.js': ['webpack']
        }
    });

    config.set(base_config);
};
