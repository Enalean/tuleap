/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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

/**
 * Configuration
 */
require('console-stamp')(console, 'isoDateTime');

var Config = require('./config/config');
var config = new Config();
config.init();

const PORT_CLIENT   = config.conf.get('port');
const TSL_KEY_PATH  = config.conf.get('full_path_ssl_key');
const TSL_CERT_PATH = config.conf.get('full_path_ssl_cert');

var fs            = require('fs');
var app           = require('express')();
var _             = require('lodash');

var server, io;
try {
    var options = {
        key: fs.readFileSync(TSL_KEY_PATH),
        cert: fs.readFileSync(TSL_CERT_PATH)
    };
    server = require('https').Server(options, app);
    io     = require('socket.io')(server);
    server.listen(PORT_CLIENT);
    config.dropRootPrivileges();
} catch (err) {
    console.error('Be careful,' + err.message.split(',')[1]);
    process.exit(1);
}

/**
 * Load main controller
 */
var MainController = require('./backend/controllers/main-controller');
new MainController(io, app, config);