'use strict';
var Rights = require('../../../backend/modules/rights');
var rights = new Rights();

var Rooms = require('../../../backend/modules/rooms');
var rooms = new Rooms(rights);

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
            on: function(){},
            to: function(){return socket_in_room;},
            leave: function(){},
            emit: function(){},
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

        spyOn(socket, 'on');
        spyOn(socket, 'to');
        spyOn(socket, 'leave');
        spyOn(socket, "emit");
        spyOn(socket, 'disconnect');
        spyOn(console, 'error');

        rights.addRightsByUserId(user_id, groups);
    });

    it("Given a room id and a socket, when I addSocketByRoomId rooms then rooms is with room id as key and an array of sockets as value", function() {
        var expect_rooms = {
            20: [socket]
        };
        expect(rooms.addSocketByRoomId).toBeDefined();
        expect(rooms.addSocketByRoomId(room_id, socket)).toEqual(true);
        expect(rooms.sockets_collection).toEqual(expect_rooms);
    });

    it("Given rights, socket sender and message to broadcast, when I broadcastData with incorrect rights then console output", function() {
        var data = {
            rights: {}
        };
        rooms.broadcastData(socket, data);
        expect(console.error).toHaveBeenCalled();
    });

    it("Given rights, socket sender and message to broadcast, when I broadcastData with correct rights then the socket will be called", function() {
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

    it("Given a room id and a socket to delete, when I removeByRoomIdAndSocketId then socket doesn't exist anymore in the room", function() {
        var expect_rooms = {
            20: [socket_bis]
        };
        rooms.addSocketByRoomId(room_id, socket_bis);
        expect(rooms.removeByRoomIdAndSocketId).toBeDefined();
        rooms.removeByRoomIdAndSocketId(room_id, socket);
        expect(rooms.sockets_collection).toEqual(expect_rooms);
    });

    it("Given a room id and a socket to delete, when I removeByRoomIdAndSocketId in room with only this socket then socket doesn't exist anymore and the room too", function() {
        expect(rooms.removeByRoomIdAndSocketId).toBeDefined();
        rooms.removeByRoomIdAndSocketId(room_id, socket_bis);
        expect(rooms.sockets_collection).toEqual({});
    });
});