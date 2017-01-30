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
        cert: fs.readFileSync(TSL_CERT_PATH),
        ciphers: "ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA:ECDHE-RSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-RSA-AES256-SHA:ECDHE-ECDSA-DES-CBC3-SHA:ECDHE-RSA-DES-CBC3-SHA:EDH-RSA-DES-CBC3-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA256:AES256-SHA256:AES128-SHA:AES256-SHA:DES-CBC3-SHA:!DSS",
        honorCipherOrder: true
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