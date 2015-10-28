'use strict';

var Rooms = require('../../backend/rooms');
var rooms = new Rooms();

var Rights = require('../../backend/rights');
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

        rooms.bind(room_id, socket);
        rights.bind(user_id, groups);
    });

    it("Given a user id and an array of groups, when I bind rights then rights is with user id as key and array as value", function() {
        var expect_rights = {
            165: groups
        };
        expect(rights.bind).toBeDefined();
        expect(rights.bind(user_id, groups)).toEqual(true);
        expect(rights.ugroups_collection).toEqual(expect_rights);
    });

    it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted only on tracker and user has rights and the user isn't the submitter then true is returned", function() {
        var userRights = {
            submitter_id: 102,
            submitter_can_view: false,
            submitter_only: ['@ug_111'],
            tracker: ['@arealtime_project_admin', '@ug_159'],
            artifact: ['@arealtime_project_admin']
        };
        expect(rights.hasUserRights(165, userRights)).toEqual(true);
        expect(rights.hasUserRightsAsSubmitter(165, userRights)).toEqual(false);
        expect(rights.userCanReceiveData(165, userRights)).toEqual(true);
    });

    it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted on tracker and artifact and the user has rights and the user isn't the submitter then true is returned", function() {
        var userRights = {
            submitter_id: 102,
            submitter_can_view: true,
            submitter_only: [],
            tracker: ['@arealtime_project_admin', '@ug_159'],
            artifact: ['@arealtime_project_admin', '@ug_158']
        };
        expect(rights.hasUserRights(165, userRights)).toEqual(true);
        expect(rights.hasUserRightsAsSubmitter(165, userRights)).toEqual(false);
        expect(rights.userCanReceiveData(165, userRights)).toEqual(true);
    });

    it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted on tracker and artifact and the user hasn't rights and the user isn't the submitter then false is returned", function() {
        var userRights = {
            submitter_id: 102,
            submitter_can_view: true,
            submitter_only: [],
            tracker: ['@arealtime_project_admin', '@ug_159'],
            artifact: ['@arealtime_project_admin', '@ug_111']
        };
        expect(rights.hasUserRights(165, userRights)).toEqual(false);
        expect(rights.hasUserRightsAsSubmitter(165, userRights)).toEqual(false);
        expect(rights.userCanReceiveData(165, userRights)).toEqual(false);
    });

    it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted on tracker and artifact and the user hasn't rights and the user is the submitter then true is returned", function() {
        var userRights = {
            submitter_id: 165,
            submitter_can_view: true,
            submitter_only: ['@ug_159'],
            tracker: ['@arealtime_project_admin', '@ug_159'],
            artifact: ['@arealtime_project_admin', '@ug_111']
        };
        expect(rights.hasUserRights(165, userRights)).toEqual(false);
        expect(rights.hasUserRightsAsSubmitter(165, userRights)).toEqual(true);
        expect(rights.userCanReceiveData(165, userRights)).toEqual(true);
    });

    it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted only on tracker and user hasn't rights and the user is the submitter then true is returned", function() {
        var userRights = {
            submitter_id: 165,
            submitter_can_view: true,
            submitter_only: ['@ug_159'],
            tracker: ['@arealtime_project_admin', '@ug_111'],
            artifact: ['@arealtime_project_admin']
        };
        expect(rights.hasUserRights(165, userRights)).toEqual(false);
        expect(rights.hasUserRightsAsSubmitter(165, userRights)).toEqual(true);
        expect(rights.userCanReceiveData(165, userRights)).toEqual(true);
    });

    it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted only on tracker and the user hasn't rights and the user is the submitter but can't view then false is returned", function() {
        var userRights = {
            submitter_id: 165,
            submitter_can_view: false,
            submitter_only: ['@ug_159'],
            tracker: ['@arealtime_project_admin', '@ug_111'],
            artifact: ['@arealtime_project_admin']
        };
        expect(rights.hasUserRights(165, userRights)).toEqual(false);
        expect(rights.hasUserRightsAsSubmitter(165, userRights)).toEqual(false);
        expect(rights.userCanReceiveData(165, userRights)).toEqual(false);
    });

    it("Given user id and user rights object for a message, when I userCanReceiveData with correct rights with permissions restricted only on tracker and the user hasn't rights and the user isn't the submitter then false is returned", function() {
        var userRights = {
            submitter_id: 102,
            submitter_can_view: true,
            submitter_only: ['@ug_111'],
            tracker: ['@arealtime_project_admin','@ug_111'],
            artifact: ['@arealtime_project_admin']
        };
        expect(rights.hasUserRights(165, userRights)).toEqual(false);
        expect(rights.hasUserRightsAsSubmitter(165, userRights)).toEqual(false);
        expect(rights.userCanReceiveData(165, userRights)).toEqual(false);
    });

    it("Given user id and user rights object for a message, when I userCanReceiveData with incorrect rights with permissions restricted only on tracker the user has rights and the user is the submitter then true is returned", function() {
        var userRights = {
            submitter_id: 165,
            submitter_can_view: true,
            submitter_only: ['@ug_101'],
            tracker: ['@arealtime_project_admin', '@ug_159'],
            artifact: ['@arealtime_project_admin']
        };
        expect(rights.hasUserRights(165, userRights)).toEqual(true);
        expect(rights.hasUserRightsAsSubmitter(165, userRights)).toEqual(true);
        expect(rights.userCanReceiveData(165, userRights)).toEqual(true);
    });

    it("Given user id and user rights object for a message with an artifact, when I filterMessageByRights with field rights restricted then we transform message content corresponding to the user rights", function() {
        var userRights = {
            field: {
                '352': ['@ug_105']
            }
        };
        var data = {
            artifact: {
                label: '1',
                card_fields: [
                    {
                        field_id: 352,
                        label: 'Summary'
                    }
                ]
            }
        };
        expect(rights.filterMessageByRights(165, userRights, data.artifact)).toEqual({
            label: null,
            card_fields: []
        });
    });

    it("Given user id and user rights object for a message with an artifact, when I filterMessageByRights with field rights not restricted then we don't transform message", function() {
        var userRights = {
            field: {
                '352': ['@site_active']
            }
        };
        var data = {
            artifact: {
                label: '1',
                card_fields: [
                    {
                        field_id: 352,
                        label: 'Summary'
                    }
                ]
            }
        };
        expect(rights.filterMessageByRights(165, userRights, data.artifact)).toEqual(data.artifact);
    });
});