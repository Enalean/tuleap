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
 * Version
 */
var packageJson = require('./package.json');

/**
 * Configuration
 */
require('console-stamp')(console, 'HH:MM:ss.l');

var Config = require('./config/config');
var config = new Config();
config.init();

const PORT_CLIENT   = config.conf.get('port');
const PRIVATE_KEY   = config.conf.get('nodejs_server_jwt_private_key');
const TSL_KEY_PATH  = config.conf.get('full_path_ssl_key');
const TSL_CERT_PATH = config.conf.get('full_path_ssl_cert');

var fs            = require('fs');
var bodyParser    = require('body-parser');
var jsonParser    = bodyParser.json();
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
 * Load modules
 */
var JWT = require('./backend/jwt');
var jwt = new JWT(PRIVATE_KEY);

var Rooms = require('./backend/rooms');
var rooms = new Rooms();

var Rights = require('./backend/rights');
var rights = new Rights();

/**
 * Connection Websocket
 *
 * To do a subscription we need to have:
 *      - string    version : To connect Client version to NodeJS server version
 *      - string    token   : To have correct information on user
 *      - string    uuid    : To distinguish each client with the same user id
 *      - int       room_id : Id to broadcast at a specific room
 */
io.on('connection', function (socket) {
    socket.auth = false;

    socket.on('subscription', function (data) {
        if (checkSubscribeCorrect(data)) {
            if (data.nodejs_server_version === packageJson.version) {
                var decoded = jwt.decodeToken(data.token, function (err) {
                    console.error(err);
                });
                if (jwt.isTokenValid(decoded)) {
                    if (!jwt.isDateExpired(decoded.exp)) {
                        subscribe(socket, data, decoded);
                    } else {
                        socket.emit('error-jwt', 'JWTExpired');
                    }
                } else {
                    console.error('JWT sent by client isn\'t correct.');
                }
            } else {
                console.error('Client needs an other Node.js version.');
            }
        } else {
            console.error('Subscription details are incorrect.');
        }

        if (!socket.auth) {
            console.log("Disconnecting socket ", socket.id);
            socket.disconnect('unauthorized');
        }
    });

    socket.on('error', function (message) {
        console.error(message);
    });

    socket.on('disconnect', function () {
        if (socket.room) {
            rights.remove(socket.room);
            rooms.removeById(socket.room, socket.id);
            console.log('Client (user id:' + socket.username + ') is disconnected.');
        }
    });
});

/**
 * POST Message when client want to broadcast data
 *
 * Broadcast data to others client in the same room
 *
 * @url POST /message
 *
 * @param sender_user_id    (int)    : id of user
 * @param sender_uuid       (string) : uuid to distinguish client with same user id
 * @param room_id           (int)    : room's id to broadcast message to this room
 * @param rights            (Array)  : to send at clients who have rights
 *                                      {
 *                                          submitter_id   (int)
 *                                          submitter_only (Array)
 *                                          tracker        (Array)
 *                                          artifact       (Array)
 *                                      }
 * @param cmd               (String) : broadcast on event command
 * @param data              (Object) : data broadcasting
 */
app.post('/message', jsonParser, function (req, res) {
    var room_id           = req.body.room_id;
    var sender_user_id    = req.body.sender_user_id;
    var sender_uuid       = req.body.sender_uuid;

    var room = rooms.get(room_id);
    if (room && room.length > 0) {
        room = rooms.update(room_id, jwt, req.body);

        var socketSender = _.find(room, function(socket) {
            return socket.username === sender_user_id && socket.uuid === sender_uuid;
        });

        if (socketSender) {
            rooms.broadcastData(rights, socketSender, req.body);
        }
    }
    res.end();
});

/**
 * Function to check if tuleap clients send
 * correct data with a room_id, uuid, and a token
 *
 * @param data (Object): data sent by tuleap client
 * @returns {boolean}
 */
function checkSubscribeCorrect(data) {
    return _.has(data, 'nodejs_server_version')
        && _.has(data, 'token')
        && _.has(data, 'room_id')
        && _.has(data, 'uuid');
}

/**
 * Subscribe a websocket
 * to receive actions
 *
 * @param socket
 * @param data    (Object): data sent by tuleap client
 * @param decoded (Object): data sent by tuleap server
 */
function subscribe(socket, data, decoded) {
    socket.uuid = data.uuid;
    socket.room = data.room_id;
    socket.username = decoded.data.user_id;
    socket.expired = decoded.exp;

    rooms.updateTokenExpiredForUser(socket.username, decoded.exp);
    socket.join(data.room_id);

    rooms.bind(data.room_id, socket);
    rights.bind(decoded.data.user_id, decoded.data.user_rights);

    if (socket.lastAction) {
        rooms.broadcastData(rights, socket, socket.lastAction);
        delete socket.lastAction;
    }

    socket.auth = true;
    console.log('Client (user id: ' + decoded.data.user_id + ') is subscribed.');
}