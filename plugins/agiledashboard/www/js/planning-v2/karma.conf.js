/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

const path = require("path");
const webpack = require("webpack"); // eslint-disable-line import/no-extraneous-dependencies
const webpack_config = require("./webpack.config.js");
const karma_configurator = require("../../../../../tools/utils/scripts/karma-configurator.js");

webpack_config.mode = "development";
webpack_config.plugins = [
    ...webpack_config.plugins,
    // Fix ngVue's stupid logger
    new webpack.DefinePlugin({ "process.env.BABEL_ENV": JSON.stringify("test") })
];

module.exports = function(config) {
    const coverage_dir = path.resolve(__dirname, "./coverage");
    const base_config = karma_configurator.setupBaseKarmaConfig(
        config,
        webpack_config,
        coverage_dir
    );

    Object.assign(base_config, {
        files: [
            karma_configurator.jasmine_promise_matchers_path,
            "node_modules/jquery/dist/jquery.js",
            karma_configurator.jasmine_fixtures_path,
            "src/app/app.spec.js"
        ],
        preprocessors: {
            "src/app/app.spec.js": ["webpack"]
        }
    });

    config.set(base_config);
};
