'use strict';

import { describe, it, expect, beforeEach } from 'vitest';

var Rooms = require('../../../backend/modules/rooms');
var rooms = new Rooms();

var Rights = require('../../../backend/modules/rights');
var rights = new Rights();

describe("Module Rights", function() {
    var groups, user_id, room_id, socket;

    beforeEach(function() {
        groups  = [ '@site_active', '@ug_101', '@ug_159', '@ug_158'];
        user_id = 165;
        room_id = 20;
        socket  = {
            id        : 'socket_id',
            username  : 165
        };

        rooms.addSocketByRoomId(room_id, socket);
        rights.addRightsByUserId(user_id, groups);
    });

    describe("addRightsByUserId()", function() {
        it("Given a user id and an array of groups, when I addRightsByUserId rights then rights is with user id as key and array as value", function () {
            var expect_rights = {
                165: groups
            };
            expect(rights.addRightsByUserId).toBeDefined();
            expect(rights.addRightsByUserId(user_id, groups)).toEqual(true);
            expect(rights.ugroups_collection).toEqual(expect_rights);
        });
    });

    describe("update()", function() {
        it("Given a user id and an array of new groups, when I update rights then rights is with user id as key and new array groups as value", function () {
            var new_groups = ['@site_active', '@ug_158'];
            var expect_rights = {
                165: new_groups
            };
            expect(rights.update).toBeDefined();
            rights.update(user_id, new_groups);
            expect(rights.ugroups_collection).toEqual(expect_rights);
        });

        it("Given a user id who doesn't exist and an array of new groups, when I update rights then user id with array groups is added", function () {
            var new_groups = ['@site_active', '@ug_158'];
            var expect_rights = {
                165: groups,
                111: new_groups
            };
            expect(rights.update).toBeDefined();
            rights.update(111, new_groups);
            expect(rights.ugroups_collection).toEqual(expect_rights);
        });
    });

    describe("userCanReceiveData()", function() {
        it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted only on tracker and user has rights and the user isn't the submitter then true is returned", function () {
            var userRights = {
                submitter_id: 102,
                submitter_can_view: false,
                submitter_only: ['@ug_111'],
                tracker: ['@arealtime_project_admin', '@ug_159'],
                artifact: []
            };
            expect(rights.userCanReceiveData(165, userRights)).toEqual(true);
        });

        it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted on tracker and artifact and the user has rights and the user isn't the submitter then true is returned", function () {
            var userRights = {
                submitter_id: 102,
                submitter_can_view: true,
                submitter_only: [],
                tracker: ['@arealtime_project_admin', '@ug_159'],
                artifact: ['@arealtime_project_admin', '@ug_158']
            };
            expect(rights.userCanReceiveData(165, userRights)).toEqual(true);
        });

        it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted on tracker and artifact and the user hasn't rights and the user isn't the submitter then false is returned", function () {
            var userRights = {
                submitter_id: 102,
                submitter_can_view: true,
                submitter_only: [],
                tracker: ['@arealtime_project_admin', '@ug_159'],
                artifact: ['@arealtime_project_admin', '@ug_111']
            };
            expect(rights.userCanReceiveData(165, userRights)).toEqual(false);
        });

        it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted on tracker and artifact and the user hasn't rights and the user is the submitter then true is returned", function () {
            var userRights = {
                submitter_id: 165,
                submitter_can_view: true,
                submitter_only: ['@ug_159'],
                tracker: ['@arealtime_project_admin', '@ug_159'],
                artifact: ['@arealtime_project_admin', '@ug_111']
            };
            expect(rights.userCanReceiveData(165, userRights)).toEqual(true);
        });

        it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted only on tracker and user hasn't rights and the user is the submitter then true is returned", function () {
            var userRights = {
                submitter_id: 165,
                submitter_can_view: true,
                submitter_only: ['@ug_159'],
                tracker: ['@arealtime_project_admin', '@ug_111'],
                artifact: ['@arealtime_project_admin']
            };
            expect(rights.userCanReceiveData(165, userRights)).toEqual(true);
        });

        it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted only on tracker and the user hasn't rights and the user is the submitter but can't view then false is returned", function () {
            var userRights = {
                submitter_id: 165,
                submitter_can_view: false,
                submitter_only: ['@ug_159'],
                tracker: ['@arealtime_project_admin', '@ug_111'],
                artifact: ['@arealtime_project_admin']
            };
            expect(rights.userCanReceiveData(165, userRights)).toEqual(false);
        });

        it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted only on tracker and the user hasn't rights and the user isn't the submitter then false is returned", function () {
            var userRights = {
                submitter_id: 102,
                submitter_can_view: true,
                submitter_only: ['@ug_111'],
                tracker: ['@arealtime_project_admin', '@ug_111'],
                artifact: ['@arealtime_project_admin']
            };
            expect(rights.userCanReceiveData(165, userRights)).toEqual(false);
        });

        it("Given user id and user rights object for a message, when I userCanReceiveData with incorrect rights with permissions restricted only on tracker the user has rights and the user is the submitter then true is returned", function () {
            var userRights = {
                submitter_id: 165,
                submitter_can_view: true,
                submitter_only: ['@ug_101'],
                tracker: ['@arealtime_project_admin', '@ug_159'],
                artifact: ['@arealtime_project_admin']
            };
            expect(rights.userCanReceiveData(165, userRights)).toEqual(true);
        });
    });
});