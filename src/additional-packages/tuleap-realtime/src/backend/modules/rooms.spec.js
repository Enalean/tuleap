'use strict';

import { describe, it, expect, beforeEach, vi } from 'vitest';

var Rights = require('./rights');
var rights = new Rights();

var Rooms = require('./rooms');
var rooms = new Rooms(rights);

var Executions = require('./executions');

describe("Module Rooms", function() {
    var groups, user_id, room_id, socket, socket_bis, socket_in_room;

    beforeEach(function() {
        groups  = [ '@site_active', '@ug_101', '@ug_159'];
        user_id = 165;
        room_id = 20;
        socket_in_room = {
            id: 'socket_id_in_room',
            on: function(){},
            leave: function(){},
            emit: function(){},
            disconnect: function(){}
        };

        socket = {
            id: 'socket_id',
            username: user_id,
            on: vi.fn(),
            to: function(){return socket_in_room;},
            leave: function(){},
            emit: vi.fn(),
            disconnect: function(){}
        };

        socket_bis = {
            id: 'socket_id_bis',
            on: function(){},
            to: function(){return socket_in_room;},
            leave: function(){},
            emit: function(){},
            disconnect: function(){}
        };

        vi.spyOn(console, "error").mockImplementation(() => {});

        rights.addRightsByUserId(user_id, groups);

        rooms.executions_collection[room_id] = new Executions();
    });

    describe("addSocketByRoomId()", function() {
        it("Given a room id and a socket, when I addSocketByRoomId rooms then rooms is with room id as key and an array of sockets as value", function () {
            var expect_rooms = {
                20: [socket]
            };
            expect(rooms.addSocketByRoomId).toBeDefined();
            expect(rooms.addSocketByRoomId(room_id, socket)).toEqual(true);
            expect(rooms.sockets_collection).toEqual(expect_rooms);
        });
    });

    describe("broadcastData()", function() {
        it("Given rights, socket sender and message to broadcast, when I broadcast data with incorrect rights then console output", function () {
            var data = {
                rights: {}
            };
            rooms.broadcastData(socket, data);
            expect(console.error).toHaveBeenCalled();
        });

        it("Given rights, socket sender and message to broadcast, when I broadcast data with correct rights then the socket will be called", function () {
            var data = {
                rights: {
                    submitter_id: 102,
                    submitter_can_view: false,
                    submitter_only: ['@ug_111'],
                    tracker: ['@arealtime_project_admin'],
                    artifact: ['@arealtime_project_admin']
                }
            };
            rooms.broadcastData(socket, data);
            expect(console.error).not.toHaveBeenCalled();
        });

        it("Given rights, socket sender and message to broadcast, when I broadcast data with correct rights and with presences on execution message then message data is transformed ", function () {
            var message = {
                room_id : room_id,
                rights: {
                    submitter_id: 165,
                    submitter_can_view: false,
                    submitter_only: ['@ug_111'],
                    tracker: ['@arealtime_project_admin'],
                    artifact: ['@arealtime_project_admin']
                },
                data: {
                    presence: {
                        execution_id: '39',
                        uuid: '123',
                        remove_from: '',
                        user: {
                            id: user_id
                        }
                    }
                }
            };

            var expect_data = {
                user: {
                    id: user_id,
                    uuid: '123'
                },
                execution_to_add: '39',
                execution_presences_to_add: [
                    {
                        id: user_id,
                        uuid: '123'
                    }
                ]
            };

            rooms.broadcastData(socket, message);
            expect(message.data).toEqual(expect_data);
            expect(console.error).not.toHaveBeenCalled();
        });

        it("Given rights, socket sender and message to broadcast, when I broadcast data with correct rights and with status change execution message", function () {
            var message = {
                room_id : room_id,
                rights: {
                    submitter_id: 165,
                    submitter_can_view: false,
                    submitter_only: ['@ug_111'],
                    tracker: ['@arealtime_project_admin'],
                    artifact: ['@arealtime_project_admin']
                },
                data: {
                    artifact_id: 39,
                    status: 'passed',
                    previous_status: 'failed',
                    previous_user: {
                        id: user_id
                    },
                    user: {
                        id: user_id
                    }
                }
            };

            var expect_data = {
                artifact_id: 39,
                status: 'passed',
                previous_status: 'failed',
                previous_user: {
                    id: user_id
                },
                user: {
                    id: user_id
                }
            };

            rooms.broadcastData(socket, message);
            expect(message.data).toEqual(expect_data);
            expect(console.error).not.toHaveBeenCalled();
        });
    });

    describe("removeByRoomIdAndSocketId()", function() {
        it("Given a room id and a socket to delete, when I removeByRoomIdAndSocketId then socket doesn't exist anymore in the room", function () {
            var expect_rooms = {
                20: [socket_bis]
            };
            rooms.addSocketByRoomId(room_id, socket_bis);
            expect(rooms.removeByRoomIdAndSocketId).toBeDefined();
            rooms.removeByRoomIdAndSocketId(room_id, socket);
            expect(rooms.sockets_collection).toEqual(expect_rooms);
        });

        it("Given a room id and a socket to delete, when I removeByRoomIdAndSocketId in room with only this socket then socket doesn't exist anymore and the room too", function () {
            expect(rooms.removeByRoomIdAndSocketId).toBeDefined();
            rooms.removeByRoomIdAndSocketId(room_id, socket_bis);
            expect(rooms.sockets_collection).toEqual({});
        });
    });
});
