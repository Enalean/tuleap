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

require('console-stamp')(console, 'isoDateTime');

const private_key = process.env.PRIVATE_KEY;
if (private_key === undefined) {
    console.log("PRIVATE_KEY missing");
    process.exit(1);
}

var app           = require('express')();

let io;
try {
    const server = require('node:http').createServer(app);
    io     = require('socket.io')(server, {path: "/local-socket.io/"});
    server.listen(2999, '127.0.0.1');
} catch (err) {
    console.error('Be careful,' + err.message.split(',')[1]);
    process.exit(1);
}

/**
 * Load main controller
 */
var MainController = require('./backend/controllers/main-controller');
new MainController(io, app, private_key);
