/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

if (typeof define !== 'function') {
    var define = require('amdefine')(module);
}

define(['convict', 'optimist', 'jsonfile'], function (convict, optimist, jsonfile) {

    var config = function () {
        this.conf = convict({});
        this.argv = optimist.argv;

        /**
         * @access public
         *
         * Function to merge default
         * configurations and user config
         * file
         */
        this.init = function() {
            var defaultConfig = require('./config.json');
            this.conf.load(defaultConfig);

            if(this.argv.config) {
                var config;
                try {
                    config = jsonfile.readFileSync(this.argv.config);
                } catch (e) {
                    console.log('The json config file isn\'t valid.');
                }
                if(config) {
                    this.conf.load(config);
                }
            }
        };

        /**
         * @access public
         *
         * Function to drop the root
         * privileges to not be run as root
         */
        this.dropRootPrivileges = function () {
            if (process.setgid) {
                try {
                    process.setgid(this.conf.get('process_gid'));
                    console.log('New gid: ' + process.getgid());
                }
                catch (err) {
                    console.log('Failed to set gid: ' + err);
                }
            }

            if (process.setuid) {
                try {
                    process.setuid(this.conf.get('process_uid'));
                    console.log('New uid: ' + process.getuid());
                }
                catch (err) {
                    console.log('Failed to set uid: ' + err);
                }
            }
        };
    };

    return config;
});