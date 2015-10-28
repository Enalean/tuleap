'use strict';

var Rooms = require('../../backend/rooms');
var rooms = new Rooms();

var Rights = require('../../backend/rights');
var rights = new Rights();

describe("Module Rooms", function() {
    var groups, user_id, room_id, socket;

    beforeEach(function() {
        groups  = [ '@site_active', '@ug_101', '@ug_159'];
        user_id = 165;
        room_id = 20;
        socket = {
            id: 'socket_id',
            on: function(){},
            leave: function(){},
            emit: function(){},
            disconnect: function(){}
        };

        spyOn(socket, 'on');
        spyOn(socket, 'leave');
        spyOn(socket, 'emit');
        spyOn(socket, 'disconnect');
        spyOn(console, 'error');

        rights.bind(user_id, groups);
    });

    it("Given a room id and a socket, when I bind rooms then rooms is with room id as key and an array of sockets as value", function() {
        var expect_rooms = {
            20: [socket]
        };
        expect(rooms.bind).toBeDefined();
        expect(rooms.bind(room_id, socket)).toEqual(true);
        expect(rooms.sockets_collection).toEqual(expect_rooms);
    });

    it("Given rights, socket sender and message to broadcast, when I broadcastData with incorrect rights then console output", function() {
        var data = {
            rights: {}
        };
        rooms.broadcastData(rights, socket, data);
        expect(console.error).toHaveBeenCalled();
    });

    it("Given rights, socket sender and message to broadcast, when I broadcastData with correct rights then console output", function() {
        var data = {
            rights: {
                submitter_id: 102,
                submitter_can_view: false,
                submitter_only: ['@ug_111'],
                tracker: ['@arealtime_project_admin'],
                artifact: ['@arealtime_project_admin']
            }
        };
        rooms.broadcastData(rights, socket, data);
        expect(console.error).not.toHaveBeenCalled();
    });

    it("Given a room id and a socket's id to delete, when I removeById then socket doesn't exist anymore in the room", function() {
        var expect_rooms = {
            20: []
        };
        expect(rooms.removeById).toBeDefined();
        rooms.removeById(room_id, 'socket_id');
        expect(rooms.sockets_collection).toEqual(expect_rooms);
    });
});