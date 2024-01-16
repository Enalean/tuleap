/**
 * Copyright (c) Enalean, 2015-2017. All Rights Reserved.
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

const _ = require("lodash");
const Executions = require("../modules/executions");
const MessageContentVerifier = require("../services/message-content-verifier");

module.exports = function (rights) {
    var self = this;

    self.sockets_collection    = {};
    self.executions_collection = {};
    self.rights                = rights;

    /**
     * @access public
     *
     * Function to get by room id
     *
     * @param room_id (string)
     * @returns {*}
     */
    self.get = function(room_id) {
        return self.sockets_collection[room_id];
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
    self.addSocketByRoomId = function(room_id, socket) {
        try {
            if (! _.has(self.sockets_collection, room_id)) {
                self.sockets_collection[room_id] = [];
                self.executions_collection[room_id] = new Executions();
            }
            self.sockets_collection[room_id].push(socket);
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
     * @param collection (Object): collection to remove
     */
    self.remove = function(room_id, collection) {
        delete collection[room_id];
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
    self.removeByRoomIdAndSocketId = function(room_id, socket_to_remove) {
        _.remove(self.sockets_collection[room_id], function(socket) {
            return socket.id === socket_to_remove.id;
        });

        self.executions_collection[room_id].removeByUserUUID(socket_to_remove.uuid);

        if (socket_to_remove.to(room_id)) {
            socket_to_remove.to(room_id).emit('user:leave', socket_to_remove.uuid);
        }
        socket_to_remove.leave(room_id);
        socket_to_remove.disconnect();

        if (_.isEmpty(self.sockets_collection[room_id])) {
            self.remove(room_id, self.sockets_collection);
            self.remove(room_id, self.executions_collection);
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
    self.updateTokenExpiredForUser = function(user_id, expired) {
        _.forEach(self.sockets_collection, function(room) {
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
    self.update = function(room_id, jwt, data) {
        var sender_user_id    = data.sender_user_id;
        var sender_uuid       = data.sender_uuid;

        var sockets = [];
        _.forEach(self.sockets_collection[room_id], function (socket) {
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

        self.sockets_collection[room_id] = sockets;
        return sockets;
    };

    /**
     * @access public
     *
     * Function to broadcast data in room
     *
     * @param socket_sender (Object): user who sends action
     * @param message       (Object): information to broadcast
     */
    self.broadcastData = function(socket_sender, message) {
        var room_id = message.room_id;
        var room    = self.get(room_id);

        if (! self.rights.isRightsContentWellFormed(message.rights)) {
            console.error('User rights sent are incorrect.');
            return;
        }

        if (MessageContentVerifier.isExecutionDeleted(message)) {
            self.executions_collection[room_id].remove(message.data.artifact_id);
        } else if (MessageContentVerifier.hasPresencesOnExecutions(message.data)) {
            message.data = self.executions_collection[room_id].update(message.data.presence);
        }
        socketSenderBroadcasts(room, socket_sender, message, message.data);
    };

    /**
     * @access public
     *
     * Function to broadcast data to all users in room
     *
     * @param message (Object): information to broadcast
     */
    self.broadcastDataToAll = function (message) {
        var room_id     = message.room_id;
        var room        = self.get(room_id);
        var rights_user = message.rights;
        var data        = message.data;

        if (! self.rights.isRightsContentWellFormed(rights_user)) {
            console.error('User rights sent are incorrect.');
            return;
        }

        _.filter(room, function (socket) {
            return (self.rights.userCanReceiveData(socket.username, rights_user));
        }).forEach(function (socket) {
            socket.emit(message.cmd, data);
        });
    };

    /**
     * @access public
     *
     * Function to emit presences
     * by room
     *
     * @param socket (Object)
     */
    self.emitPresences = function(socket) {
        var presences_collection = self.executions_collection[socket.room].presences_collection;

        socket.emit('presences', presences_collection);
    };

    /**
     * @access private
     *
     * Function to broadcast data to users
     * who have rights
     *
     * @param room          (Object): room who contains users
     * @param socket_sender (Object): user who sends action
     * @param message       (Object): information to broadcast
     * @param data          (Object): data for user client
     */
    function socketSenderBroadcasts(room, socket_sender, message, data) {
        var rights_user = message.rights;

        _.filter(room, function (socket) {
            return (socket.id !== socket_sender.id
            && self.rights.userCanReceiveData(socket.username, rights_user));
        }).forEach(function (socket) {
            socket_sender.to(socket.id).emit(message.cmd, data);
        });
    }
};
