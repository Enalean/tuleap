'use strict';

import { describe, beforeEach, it, expect } from 'vitest';

var Executions = require('../../../backend/modules/executions');
var executions = new Executions();

describe("Module Executions", function() {
    var execution_id, presence;

    beforeEach(function() {
        execution_id = 5;
        presence = {
            id: 101,
            uuid: '123'
        };

        executions.presences_collection = {};
        executions.addUserByExecutionId(execution_id, presence);
    });

    describe("addUserByExecutionId()", function() {
        it("Given a execution id and a presence, when I add user by execution id then executions is with execution id as key and an array of presence as value", function () {
            var presence = {
                id: 102,
                uuid: '456'
            };

            var expect_executions = {
                1: [
                    {
                        id: 102,
                        uuid: '456'
                    }
                ],
                5: [
                    {
                        id: 101,
                        uuid: '123'
                    }
                ]
            };
            expect(executions.addUserByExecutionId).toBeDefined();
            expect(executions.addUserByExecutionId(1, presence)).toEqual(true);
            expect(executions.presences_collection).toEqual(expect_executions);
        });
    });

    describe("update()", function() {
        it("Given a execution id, uuid of user and remove_from, when I update executions with a uuid who already exist then it's removed and added correctly", function () {
            var data = {
                execution_id: 1,
                uuid: '123',
                remove_from: 5,
                user: {
                    id: 101
                }
            };

            var expect_data = {
                execution_to_add: 1,
                execution_presences_to_add: [
                    {
                        id: 101,
                        uuid: '123'
                    }
                ],
                execution_to_remove: 5,
                execution_presences_to_remove: [],
                user: {
                    id: 101,
                    uuid: '123'
                }
            };

            var expect_executions = {
                1: [
                    {
                        id: 101,
                        uuid: '123'
                    }
                ],
                5: []
            };

            expect(executions.update).toBeDefined();
            expect(executions.update(data)).toEqual(expect_data);
            expect(executions.presences_collection).toEqual(expect_executions);
        });

        it("Given a execution id, uuid of user and no remove_from, when I update executions with a uuid who doesn't exist then it's only added correctly", function () {
            var data = {
                execution_id: 5,
                remove_from: '',
                uuid: '789',
                user: {
                    id: 121
                }
            };

            var expect_data = {
                execution_to_add: 5,
                execution_presences_to_add: [
                    {
                        id: 101,
                        uuid: '123'
                    },
                    {
                        id: 121,
                        uuid: '789'
                    }
                ],
                user: {
                    id: 121,
                    uuid: '789'
                }
            };

            var expect_executions = {
                5: [
                    {
                        id: 101,
                        uuid: '123'
                    },
                    {
                        id: 121,
                        uuid: '789'
                    }
                ]
            };

            expect(executions.update).toBeDefined();
            expect(executions.update(data)).toEqual(expect_data);
            expect(executions.presences_collection).toEqual(expect_executions);
        });

        it("Given a execution id, uuid of user and remove_from, when I update executions with an uuid who already exist but not in remove_from then we send nothing", function () {
            var data = {
                execution_id: 3,
                remove_from: 2,
                uuid: '123',
                user: {
                    id: 101
                }
            };

            var expect_executions = {
                5: [
                    {
                        id: 101,
                        uuid: '123'
                    }
                ]
            };

            expect(executions.update).toBeDefined();
            expect(executions.update(data)).toEqual({});
            expect(executions.presences_collection).toEqual(expect_executions);
        });
    });

    describe("removeByUserUUID()", function() {
        it("Given a uuid of user, when I remove by user uuid then presence doesn't exist anymore in the execution", function () {
            var expect_executions = {
                5: [
                    {
                        id: 101,
                        uuid: '789'
                    }
                ]
            };

            executions.addUserByExecutionId(execution_id, {
                id: 101,
                uuid: '789'
            });
            expect(executions.removeByUserUUID).toBeDefined();
            executions.removeByUserUUID('123');
            expect(executions.presences_collection).toEqual(expect_executions);
        });

        it("Given a uuid of user, when I remove by user uuid with only this presence then presence doesn't exist anymore and the execution too", function () {
            expect(executions.removeByUserUUID).toBeDefined();
            executions.removeByUserUUID('123');
            expect(executions.presences_collection).toEqual({});
        });
    });
});