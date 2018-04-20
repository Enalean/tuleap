const path               = require('path');
const webpack_config     = require('./webpack.config.js')[1];
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
            'angular-artifact-modal/src/index.spec.js'
        ],
        preprocessors: {
            'angular-artifact-modal/src/index.spec.js': ['webpack']
        }
    });

    config.set(base_config);
};
