/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

if (typeof define !== 'function') {
    var define = require('amdefine')(module);
}

define([
    'body-parser',
    '../services/communication-service',
    '../services/scores-service',
    '../modules/rooms',
    '../modules/rights',
    '../modules/scores',
    '../modules/jwt'
], function (
    bodyParser,
    CommunicationService,
    ScoresService,
    Rooms,
    Rights,
    Scores,
    JWT
) {

    var MainController = function (io, app, config) {
        var jsonParser           = bodyParser.json();
        var rooms                = new Rooms(new Rights(), new Scores());
        var jwt                  = new JWT(config.conf.get('nodejs_server_jwt_private_key'));
        var communicationService = new CommunicationService(rooms, jwt);
        var scoresService        = new ScoresService(rooms.scores);

        /**
         * Connection Websocket on namespace trafficlights
         *
         * To do a subscription we need to have:
         *      - string    version : To connect Client version to Tuleap-realtime server version
         *      - string    token   : To have correct information on user
         *      - string    uuid    : To distinguish each client with the same user id
         *      - string    room_id : Id to broadcast at a specific room
         */
        io.of('trafficlights').on('connection', function (socket) {
            socket.auth = false;

            socket.on('subscription', function (data) {
                communicationService.verifyAndSubscribe(socket, data);

                if (socket.auth) {
                    communicationService.emitPresences(socket);
                    scoresService.clearScoresInSeveralDays();
                } else {
                    console.log("Disconnecting socket ", socket.id);
                    socket.disconnect('unauthorized');
                }
            });

            listenCommonEvents(socket);
        });

        /**
         * Connection Websocket on default namespace
         *
         * To do a subscription we need to have:
         *      - string    version : To connect Client version to Tuleap-realtime server version
         *      - string    token   : To have correct information on user
         *      - string    uuid    : To distinguish each client with the same user id
         *      - string    room_id : Id to broadcast at a specific room
         */
        io.on('connection', function (socket) {
            socket.auth = false;

            socket.on('subscription', function (data) {
                communicationService.verifyAndSubscribe(socket, data);

                if (! socket.auth) {
                    console.log("Disconnecting socket ", socket.id);
                    socket.disconnect('unauthorized');
                }
            });

            listenCommonEvents(socket);
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
         * @param room_id           (string) : room's id to broadcast message to this room
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
            res.end();
            communicationService.broadcast(req.body);
        });

        /**
         * Function to listen events
         *
         * @param socket
         */
        function listenCommonEvents(socket) {
            socket.on('error', function (message) {
                console.error(message);
            });

            socket.on('disconnect', function () {
                if (socket.room) {
                    communicationService.disconnect(socket);
                }
            });

            socket.on('token', function(token) {
                communicationService.refreshToken(socket, token);
            });
        }
    };

    return MainController;
});