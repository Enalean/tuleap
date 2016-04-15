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
    'lodash',
    '../../package.json',
    '../modules/jwt',
    '../modules/rooms',
    '../modules/rights'
], function (
    _,
    packageJson,
    JWT,
    Rooms,
    Rights
) {
    var CommunicationService = function (config) {
        this.jwt        = new JWT(config.conf.get('nodejs_server_jwt_private_key'));
        this.rooms      = new Rooms();
        this.rights     = new Rights();

        /**
         * @access public
         *
         * Function to verify data before
         * subscribing user
         *
         * @param socket (Object): user socket
         * @param data   (Object): user information to subscribe
         */
        this.verifyAndSubscribe = function(socket, data) {
            if (! checkSubscribeCorrect(data)) {
                console.error('Subscription details are incorrect.');
                return;
            }

            if (data.nodejs_server_version !== packageJson.version) {
                console.error('Client needs an other Node.js version.');
                return;
            }

            var decoded = this.jwt.decodeToken(data.token, function (err) {
                console.error(err);
            });

            if (! this.jwt.isTokenValid(decoded)) {
                console.error('JWT sent by client isn\'t correct.');
                return;
            }

            if (this.jwt.isDateExpired(decoded.exp)) {
                socket.emit('error-jwt', 'JWTExpired');
                return;
            }

            subscribe(this.rooms, this.rights, socket, data, decoded);
        };

        /**
         * @access public
         *
         * Function to disconnect the user
         *
         * @param socket (Object): user socket
         */
        this.disconnect = function(socket) {
            this.rights.remove(socket.room);
            this.rooms.removeByRoomIdAndSocketId(socket.room, socket);
            console.log('Client (user id: ' + socket.username + ' - room id: ' + socket.room + ') is disconnected.');
        };

        /**
         * @access public
         *
         * Function to broadcast data
         * for users
         *
         * @param data (Object): data to send
         */
        this.broadcast = function(data) {
            var room_id           = data.room_id;
            var sender_user_id    = data.sender_user_id;
            var sender_uuid       = data.sender_uuid;

            var room = this.rooms.get(room_id);
            if (room && room.length > 0) {
                room = this.rooms.update(room_id, this.jwt, data);

                var socketSender = _.find(room, function(socket) {
                    return socket.username === sender_user_id && socket.uuid === sender_uuid;
                });

                if (socketSender) {
                    console.log('Client (user id: ' + socketSender.username + ' - room id: ' + socketSender.room + ') broadcasts data.');
                    this.rooms.broadcastData(this.rights, socketSender, data);
                }
            } else {
                console.log('Room doesn\'t exist');
            }
        };

        /**
         * @access private
         *
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
         * @access private
         *
         * Subscribe a websocket
         * to receive actions
         *
         * @param rooms   (Object): module rooms
         * @param rights  (Object): module rights
         * @param socket  (Object): user socket
         * @param data    (Object): data sent by tuleap client
         * @param decoded (Object): data sent by tuleap server
         */
        function subscribe(rooms, rights, socket, data, decoded) {
            socket.uuid = data.uuid;
            socket.room = data.room_id;
            socket.username = decoded.data.user_id;
            socket.expired = decoded.exp;

            rooms.updateTokenExpiredForUser(socket.username, decoded.exp);
            socket.join(data.room_id);

            rooms.addSocketByRoomId(data.room_id, socket);
            rights.addRightsByUserId(decoded.data.user_id, decoded.data.user_rights);

            if (socket.lastAction) {
                rooms.broadcastData(rights, socket, socket.lastAction);
                delete socket.lastAction;
            }

            socket.auth = true;
            console.log('Client (user id: ' + socket.username + ' - room id: ' + socket.room + ') is subscribed.');
        }
    };

    return CommunicationService;
});