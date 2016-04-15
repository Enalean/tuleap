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

define([
    'lodash',
    '../modules/executions'
], function (
    _,
    Executions
) {
    var rooms = function () {
        this.sockets_collection = {};
        this.executions = new Executions();

        /**
         * @access public
         *
         * Function to get by room id
         *
         * @param room_id (string)
         * @returns {*}
         */
        this.get = function(room_id) {
            return this.sockets_collection[room_id];
        };

        /**
         * @access public
         *
         * Function to add for each room
         * their clients
         *
         * @param room_id (string)
         * @param socket  (Object)
         */
        this.addSocketByRoomId = function(room_id, socket) {
            try {
                if (! _.has(this.sockets_collection, room_id)) {
                    this.sockets_collection[room_id] = [];
                }
                this.sockets_collection[room_id].push(socket);
                return true;
            } catch (e) {
                return false;
            }
        };

        /**
         * @access public
         *
         * Function to remove a
         * room
         *
         * @param room_id (string)
         */
        this.remove = function(room_id) {
            delete this.sockets_collection[room_id];
        };

        /**
         * @access public
         *
         * Function to remove and disconnect
         * a socket from a room
         *
         * @param room_id          (string)
         * @param socket_to_remove (Object)
         */
        this.removeByRoomIdAndSocketId = function(room_id, socket_to_remove) {
            _.remove(this.sockets_collection[room_id], function(socket) {
                return socket.id === socket_to_remove.id;
            });

            this.executions.removeByUserUUID('uuid', socket_to_remove.uuid);

            if (socket_to_remove.to(room_id)) {
                socket_to_remove.to(room_id).emit('user:leave', socket_to_remove.uuid);
            }
            socket_to_remove.leave(room_id);
            socket_to_remove.disconnect();

            if (_.isEmpty(this.sockets_collection[room_id])) {
                this.remove(room_id);
            }
        };

        /**
         * @access public
         *
         * Function to update the expired date for a user
         *
         * @param user_id (int)
         * @param expired (int): token date
         */
        this.updateTokenExpiredForUser = function(user_id, expired) {
            _.forEach(this.sockets_collection, function(room) {
                _.find(room, function(socket) {
                    if(socket.username === user_id) {
                        socket.expired = expired;
                    }
                    return socket.username === user_id;
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
         * @param room_id  (string)
         * @param jwt      (Object)
         * @param data     (Object)
         * @returns {Array} : Array of sockets client updated
         */
        this.update = function(room_id, jwt, data) {
            var sender_user_id    = data.sender_user_id;
            var sender_uuid       = data.sender_uuid;

            var sockets = [];
            _.forEach(this.sockets_collection[room_id], function (socket) {
                if (!jwt.isDateExpired(socket.expired)) {
                    sockets.push(socket);
                } else {
                    if (socket.username === sender_user_id && socket.uuid === sender_uuid) {
                        socket.lastAction = data;
                    }
                    socket.leave(room_id);
                    socket.emit('error-jwt', 'JWTExpired');
                }
            });

            this.sockets_collection[room_id] = sockets;
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
            var self              = this;
            var room_id           = message.room_id;
            var rights_user       = message.rights;
            var room              = this.get(room_id);

            if (rights.isRightsContentWellFormed(rights_user)) {
                var data = message.data;
                if (hasMessageContentPresencesOnExecutions(data)) {
                    data = self.executions.update(data.presence);
                }

                _.filter(room, function (socket) {
                    return (socket.id !== socket_sender.id
                    && rights.userCanReceiveData(socket.username, rights_user));
                }).forEach(function (socket) {
                    if (hasMessageContentArtifact(data)) {
                        data.artifact = rights.filterMessageByRights(socket.username, rights_user, data.artifact);
                    }
                    if (! _.isEmpty(data)) {
                        socket_sender.to(socket.id).emit(message.cmd, data);
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
        function hasMessageContentArtifact(data) {
            return _.has(data, 'artifact.card_fields');
        }

        /**
         * @access private
         *
         * Function to verify if content message sent
         * a presence for an artifact
         *
         * @param data (Object)
         * @returns {boolean}
         */
        function hasMessageContentPresencesOnExecutions(data) {
            return _.has(data, 'presence.execution_id')
                && _.has(data, 'presence.remove_from')
                && _.has(data, 'presence.uuid')
                && _.has(data, 'presence.user');
        }
    };

    return rooms;
});