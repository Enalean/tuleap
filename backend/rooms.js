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

if (typeof define !== 'function') {
    var define = require('amdefine')(module);
}

define(['lodash'], function (_) {
    var rooms = function () {
        this.sockets_collection = {};

        /**
         * @access public
         *
         * Function to get by room id
         *
         * @param key (int) : room id
         * @returns {*}
         */
        this.get = function(key) {
            return this.sockets_collection[key];
        };

        /**
         * @access public
         *
         * Function to add for each room
         * their clients
         *
         * @param key   (int)   : room id
         * @param value (Object): socket client
         */
        this.bind = function(key, value) {
            try {
                if (! _.has(this.sockets_collection, key)) {
                    this.sockets_collection[key] = [];
                }
                this.sockets_collection[key].push(value);
                return true;
            } catch (e) {
                return false;
            }
        };

        /**
         * @access public
         *
         * Function to remove and disconnect
         * a from a room
         *
         * @param key   (int)   : room id
         * @param value (String): socket id
         */
        this.removeById = function(key, value) {
            _.remove(this.sockets_collection[key], function(socket) {
                if(socket.id === value) {
                    socket.leave(key);
                    socket.disconnect();
                }
                return socket.id === value;
            });
        };

        /**
         * @access public
         *
         * Function to update the expired date for a user
         *
         * @param user_id (int): user id
         * @param expired (int): token date
         */
        this.updateTokenExpiredForUser = function(user_id, expired) {
            _.forEach(this.sockets_collection, function(room) {
                _.forEach(room, function(socket) {
                    if(socket.username === user_id) {
                        socket.expired = expired;
                    }
                });
            });
        };

        /**
         * @access public
         *
         * Function to update room by id
         * checking the token dates saving
         * the last action if sender has JWT
         * expired
         *
         * @param key  (int): room id
         * @param jwt  (Object)
         * @param data (Object)
         * @returns {Array} : Array of sockets client updated
         */
        this.update = function(key, jwt, data) {
            var sender_user_id    = data.sender_user_id;
            var sender_uuid       = data.sender_uuid;

            var sockets = [];
            _.forEach(this.sockets_collection[key], function (socket) {
                if (!jwt.isDateExpired(socket.expired)) {
                    sockets.push(socket);
                } else {
                    if (socket.username === sender_user_id && socket.uuid === sender_uuid) {
                        socket.lastAction = data;
                    }
                    socket.leave(key);
                    socket.emit('error-jwt', 'JWTExpired');
                }
            });

            this.sockets_collection[key] = sockets;
            return sockets;
        };

        /**
         * @access public
         *
         * Function to broadcast data in room
         *
         * @param rights        (Object)
         * @param socket_sender (Object)
         * @param message       (Object)
         */
        this.broadcastData = function(rights, socket_sender, message) {
            var me                = this;
            var room_id           = message.room_id;
            var rights_user       = message.rights;
            var room              = this.get(room_id);

            if (rights.isRightsContentWellFormed(rights_user)) {
                _.forEach(room, function (socket) {
                    if (socket.id !== socket_sender.id) {
                        if (rights.userCanReceiveData(socket.username, rights_user)) {
                            var data = message.data;
                            if (me.hasMessageContentArtifact(data)) {
                                data.artifact = rights.filterMessageByRights(socket.username, rights_user, data.artifact);
                            }
                            socket_sender.to(socket.id).emit(message.cmd, data);
                        }
                    }
                });
            } else {
                console.error('User rights sent are incorrect.')
            }
        };

        /**
         * @access private
         *
         * Function to verify if content message sent
         * has an artifact with fields
         *
         * @param data (Object)
         * @returns {boolean}
         */
        this.hasMessageContentArtifact = function(data) {
            return _.has(data, 'artifact')
                && _.has(data.artifact, 'card_fields');
        };
    };

    return rooms;
});