/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

import { clone } from "lodash-es";
import CKEDITOR from "ckeditor4";
import execution_collection_module from "./execution-collection.js";
import angular from "angular";
import "angular-mocks";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

describe("ExecutionService", () => {
    let $q,
        $rootScope,
        wrapPromise,
        ExecutionRestService,
        SharedPropertiesService,
        editor,
        ckeditorInlineSpy,
        ckeditorRemoveSpy,
        setAttributeSpy,
        ckeditor_field,
        ExecutionService;

    beforeEach(() => {
        angular.mock.module(execution_collection_module);

        angular.mock.inject(
            function (
                _$q_,
                _$rootScope_,
                _ExecutionRestService_,
                _SharedPropertiesService_,
                _ExecutionService_,
            ) {
                $q = _$q_;
                $rootScope = _$rootScope_;
                ExecutionRestService = _ExecutionRestService_;
                SharedPropertiesService = _SharedPropertiesService_;
                ExecutionService = _ExecutionService_;
            },
        );

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    describe("loadExecutions()", () => {
        it(`Given a campaign id,
            then executions are built and sorted by categories
            and a promise will be resolved with the list of executions`, async () => {
            const campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                uri: "testmanagement_campaigns/6",
            };

            const categories_results = {
                Svn: {
                    label: "Svn",
                    executions: [
                        {
                            id: 4,
                            definition: {
                                category: "Svn",
                                description: "test",
                                id: 3,
                                summary: "My first test",
                                uri: "testmanagement_definitions/3",
                            },
                        },
                    ],
                },
            };

            const execution_results = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                },
            };

            const executions_by_categories_by_campaigns_results = {
                6: categories_results,
            };

            const response = {
                results: [
                    {
                        id: 4,
                        definition: {
                            category: "Svn",
                            description: "test",
                            id: 3,
                            summary: "My first test",
                            uri: "testmanagement_definitions/3",
                        },
                    },
                ],
                total: 1,
            };

            jest.spyOn(ExecutionService, "getAllRemoteExecutions").mockReturnValue(
                $q.when(response.results),
            );
            jest.spyOn(ExecutionRestService, "getRemoteExecutions").mockReturnValue(
                $q.when(response),
            );

            const promise = ExecutionService.loadExecutions(campaign.id);

            expect(ExecutionService.loading[campaign.id]).toBe(true);

            await expect(wrapPromise(promise)).resolves.toEqual(response.results);
            expect(ExecutionService.categories).toEqual(categories_results);
            expect(ExecutionService.executions).toEqual(execution_results);
            expect(ExecutionService.executions_by_categories_by_campaigns).toEqual(
                executions_by_categories_by_campaigns_results,
            );

            expect(ExecutionService.loading[campaign.id]).toBe(false);
        });
    });

    describe("getAllRemoteExecutions()", () => {
        it(`Given that I have more remote executions than the given fetching limit,
            when I get all remote executions,
            then all the remote executions are fetched`, async () => {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                uri: "testmanagement_campaigns/6",
            };

            var remote_executions_count = 2;

            var response = {
                results: [
                    {
                        id: 4,
                        definition: {
                            category: "Svn",
                            description: "test",
                            id: 3,
                            summary: "My first test",
                            uri: "testmanagement_definitions/3",
                        },
                    },
                ],
                total: remote_executions_count,
            };

            var get_remote_executions_request = $q.defer();
            const getRemoteExecutions = jest
                .spyOn(ExecutionRestService, "getRemoteExecutions")
                .mockReturnValue(get_remote_executions_request.promise);

            var promise = ExecutionService.getAllRemoteExecutions(campaign.id, 1, 0);
            get_remote_executions_request.resolve(response);

            await wrapPromise(promise);
            expect(getRemoteExecutions).toHaveBeenCalledTimes(2);
        });
    });

    describe("synchronizeExecutions()", () => {
        var campaign_id = 6,
            execution_1 = { id: 1, definition: { category: "Security" } },
            execution_2 = { id: 2, definition: { category: "NonRegression" } },
            service_executions = null,
            service_categories = null,
            get_remote_executions = null,
            get_all_remote_executions = null;

        var resolveExecutions = function (executions) {
            var data = executions || [];

            get_remote_executions.resolve({
                total: data.length,
                results: data,
            });
            get_all_remote_executions.resolve(data);
        };

        beforeEach(() => {
            ExecutionService.campaign_id = campaign_id;
            ExecutionService.executions = {
                1: execution_1,
                2: execution_2,
            };
            ExecutionService.executions_by_categories_by_campaigns[campaign_id] = {
                Security: {
                    label: "Security",
                    executions: [execution_1],
                },
                NonRegression: {
                    label: "NonRegression",
                    executions: [execution_2],
                },
            };

            service_executions = function () {
                return ExecutionService.executions;
            };

            service_categories = function () {
                return ExecutionService.executions_by_categories_by_campaigns[campaign_id];
            };

            get_remote_executions = $q.defer();
            jest.spyOn(ExecutionRestService, "getRemoteExecutions").mockReturnValue(
                get_remote_executions.promise,
            );

            get_all_remote_executions = $q.defer();
            jest.spyOn(ExecutionService, "getAllRemoteExecutions").mockReturnValue(
                get_all_remote_executions.promise,
            );
        });

        it(`Given that I have different sets of loaded and remote executions,
            when I synchronize them,
            then the executions not present remotely are unloaded`, async () => {
            var remote_executions = [execution_1];

            const promise = ExecutionService.synchronizeExecutions(campaign_id);
            resolveExecutions(remote_executions);
            await wrapPromise(promise);
            expect(service_executions()[2]).toBeUndefined();
            expect(service_categories().NonRegression.executions).toHaveLength(0);
        });

        it(`Given that I have different sets of loaded and remote executions,
            when I synchronize them,
            then the executions not present locally are loaded`, async () => {
            var remote_executions = [execution_1, execution_2];

            ExecutionService.executions = { 1: execution_1 };
            ExecutionService.executions_by_categories_by_campaigns[campaign_id] = {
                Security: { label: "Security", executions: [execution_1] },
            };

            const promise = ExecutionService.synchronizeExecutions(campaign_id);
            resolveExecutions(remote_executions);
            await wrapPromise(promise);
            expect(service_executions()[2]).toEqual(execution_2);
            expect(service_categories().NonRegression.executions).toHaveLength(1);
        });

        it(`Given that I have the same sets of loaded and remote executions, when I synchronize them, then the local executions are not duplicated`, async () => {
            var remote_executions = [execution_1, execution_2];

            const promise = ExecutionService.synchronizeExecutions(campaign_id);
            resolveExecutions(remote_executions);
            await wrapPromise(promise);
            expect(Object.keys(service_executions())).toHaveLength(2);
            expect(service_categories().Security.executions).toHaveLength(1);
            expect(service_categories().NonRegression.executions).toHaveLength(1);
        });
    });

    describe("getExecutionsByDefinitionId() -", function () {
        it("Given that categories, when I get executions by definition id, then only execution with definition id selected are returned", function () {
            var categories = {
                Svn: {
                    executions: [
                        {
                            id: 4,
                            definition: {
                                category: "Svn",
                                description: "test",
                                id: 3,
                                summary: "My first test",
                                uri: "testmanagement_definitions/3",
                            },
                        },
                    ],
                    label: "Svn",
                },
            };

            var executions_results = [
                {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                },
            ];

            ExecutionService.categories = categories;

            expect(ExecutionService.getExecutionsByDefinitionId(3)).toEqual(executions_results);
        });
    });

    describe("addTestExecution() -", function () {
        it("Given that campaign, when I add an execution, then it's added with values and campaign with correct numbers", function () {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_notrun: 0,
                nb_of_blocked: 0,
                total: 0,
            };

            var categories = {};

            var execution = {
                id: 4,
                status: "notrun",
                definition: {
                    category: "Svn",
                },
            };

            var executions_by_categories_by_campaigns = {
                6: categories,
            };

            ExecutionService.campaign_id = 6;
            ExecutionService.campaign = campaign;
            ExecutionService.categories = categories;
            ExecutionService.executions_by_categories_by_campaigns =
                executions_by_categories_by_campaigns;
            ExecutionService.addTestExecution(execution);
            expect(ExecutionService.executions[4]).toEqual({
                id: 4,
                status: "notrun",
                definition: {
                    category: "Svn",
                },
            });
            expect(ExecutionService.campaign).toEqual({
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_notrun: 1,
                nb_of_blocked: 0,
                total: 1,
            });
        });
    });

    describe("addTestExecutionWithoutUpdateCampaignStatus() -", function () {
        it("Given that campaign, when I add an execution, then it's added with values and campaign with correct numbers", function () {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_notrun: 0,
                nb_of_blocked: 0,
                total: 0,
            };

            var categories = {};

            var execution = {
                id: 4,
                status: "notrun",
                definition: {
                    category: "Svn",
                },
            };

            var executions_by_categories_by_campaigns = {
                6: categories,
            };

            ExecutionService.campaign_id = 6;
            ExecutionService.campaign = campaign;
            ExecutionService.categories = categories;
            ExecutionService.executions_by_categories_by_campaigns =
                executions_by_categories_by_campaigns;
            ExecutionService.addTestExecutionWithoutUpdateCampaignStatus(execution);
            expect(ExecutionService.executions[4]).toEqual({
                id: 4,
                status: "notrun",
                definition: {
                    category: "Svn",
                },
            });
            expect(ExecutionService.campaign.nb_of_notrun).toBe(0);
            expect(ExecutionService.campaign.total).toBe(0);
        });
    });

    describe("updateTestExecution() -", function () {
        beforeEach(() => {
            editor = {
                on: jest.fn(),
                destroy: jest.fn(),
                showNotification: jest.fn(),
            };
        });
        it("Given that campaign, when I update an execution, then it's updated with new values and campaign with correct numbers", function () {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_notrun: 1,
                nb_of_blocked: 0,
                total: 1,
            };

            var execution_to_save = {
                id: 4,
                status: "failed",
            };

            var executions = {
                4: {
                    id: 4,
                    previous_result: {
                        status: "notrun",
                        submitted_by: {
                            id: 5,
                        },
                    },
                },
            };

            var campaign_results = {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 1,
                nb_of_notrun: 0,
                nb_of_blocked: 0,
                total: 1,
            };
            const brodcast = jest.spyOn($rootScope, "$broadcast");

            ExecutionService.campaign = campaign;
            ExecutionService.executions = executions;
            ExecutionService.updateTestExecution(execution_to_save);

            expect(ExecutionService.executions[4].status).toBe("failed");
            expect(ExecutionService.campaign).toEqual(campaign_results);
            expect(brodcast).toHaveBeenCalledWith(
                "reload-comment-editor-view",
                ExecutionService.executions[4],
            );
        });

        it("Given that campaign, when I update an execution with different values, then the execution and the campaign must change", function () {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_notrun: 0,
                nb_of_blocked: 1,
                total: 1,
            };

            var execution_to_save = {
                id: 4,
                status: "notrun",
            };

            var executions = {
                4: {
                    id: 4,
                    previous_result: {
                        status: "blocked",
                    },
                },
            };

            var campaign_copy = clone(campaign);
            const brodcast = jest.spyOn($rootScope, "$broadcast");

            ExecutionService.campaign = campaign;
            ExecutionService.executions = executions;
            ExecutionService.updateTestExecution(execution_to_save);

            expect(ExecutionService.campaign).not.toEqual(campaign_copy);
            expect(Object.keys(ExecutionService.campaign)).toHaveLength(
                Object.keys(campaign_copy).length,
            );
            expect(brodcast).toHaveBeenCalledWith(
                "reload-comment-editor-view",
                ExecutionService.executions[4],
            );
        });

        it("Given that user is on a test that has just been updated by someone else, it postpone the update of the execution", () => {
            const current_user = {
                id: 101,
                uri: "users/101",
                uuid: "uuid-101",
            };
            jest.spyOn(SharedPropertiesService, "getCurrentUser").mockReturnValue(current_user);

            const campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_notrun: 0,
                nb_of_blocked: 1,
                total: 1,
            };

            const executions = {
                4: {
                    id: 4,
                    previous_result: {
                        status: "blocked",
                    },
                    definition: {
                        description: "Version A",
                    },
                    viewed_by: [current_user],
                },
            };

            const execution_to_save = {
                id: 4,
                status: "notrun",
                definition: {
                    description: "Version B",
                },
            };

            const updated_by = {
                id: 102,
                uri: "users/102",
                uuid: "uuid-102",
            };

            const brodcast = jest.spyOn($rootScope, "$broadcast");

            ExecutionService.campaign = campaign;
            ExecutionService.executions = executions;
            ExecutionService.updateTestExecution(execution_to_save, updated_by);

            expect(ExecutionService.executions[4].definition.description).toBe("Version A");
            expect(
                ExecutionService.executions[4].userCanReloadTestBecauseDefinitionIsUpdated,
            ).toBeTruthy();

            ExecutionService.executions[4].userCanReloadTestBecauseDefinitionIsUpdated();

            expect(ExecutionService.executions[4].definition.description).toBe("Version B");
            expect(
                ExecutionService.executions[4].userCanReloadTestBecauseDefinitionIsUpdated,
            ).toBeFalsy();
            expect(brodcast).toHaveBeenCalledWith(
                "reload-comment-editor-view",
                ExecutionService.executions[4],
            );
        });
    });

    describe("removeTestExecution() -", function () {
        it("Given that campaign, when I remove an execution, then it's removed from executions and categories and campaign numbers are updated", function () {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_notrun: 1,
                nb_of_blocked: 0,
                total: 1,
            };

            var execution = {
                id: 4,
                status: "notrun",
                definition: {
                    category: "Svn",
                },
            };

            var categories = {
                Svn: {
                    label: "Svn",
                    executions: [execution],
                },
            };

            var executions_by_categories_by_campaigns = {
                6: categories,
            };

            ExecutionService.campaign_id = 6;
            ExecutionService.campaign = campaign;
            ExecutionService.categories = categories;
            ExecutionService.executions_by_categories_by_campaigns =
                executions_by_categories_by_campaigns;
            ExecutionService.executions = { 4: execution };

            ExecutionService.removeTestExecution(execution);

            expect(ExecutionService.executions[4]).toBeUndefined();
            expect(
                ExecutionService.executions_by_categories_by_campaigns[6].Svn.executions[4],
            ).toBeUndefined();
            expect(ExecutionService.campaign.nb_of_notrun).toBe(0);
            expect(ExecutionService.campaign.total).toBe(0);
        });
    });

    describe("removeTestExecutionWithoutUpdateCampaignStatus() -", function () {
        it("Given that campaign, when I remove an execution, then it's removed from executions and categories and campaign numbers are updated", function () {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_notrun: 1,
                nb_of_blocked: 0,
                total: 1,
            };

            var execution = {
                id: 4,
                status: "notrun",
                definition: {
                    category: "Svn",
                },
            };

            var categories = {
                Svn: {
                    label: "Svn",
                    executions: [execution],
                },
            };

            var executions_by_categories_by_campaigns = {
                6: categories,
            };

            ExecutionService.campaign_id = 6;
            ExecutionService.campaign = campaign;
            ExecutionService.categories = categories;
            ExecutionService.executions_by_categories_by_campaigns =
                executions_by_categories_by_campaigns;
            ExecutionService.executions = { 4: execution };

            ExecutionService.removeTestExecutionWithoutUpdateCampaignStatus(execution);

            expect(ExecutionService.executions[4]).toBeUndefined();
            expect(
                ExecutionService.executions_by_categories_by_campaigns[6].Svn.executions[4],
            ).toBeUndefined();
            expect(ExecutionService.campaign.nb_of_notrun).toBe(1);
            expect(ExecutionService.campaign.total).toBe(1);
        });
    });

    describe("updateCampaign() -", function () {
        it("Given that campaign, when I update it, then it's updated with new values", function () {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                uri: "testmanagement_campaigns/6",
            };

            var campaign_updated = {
                id: "5",
                label: "Release 2",
                status: "Open",
                uri: "testmanagement_campaigns/6",
            };

            ExecutionService.campaign = campaign;
            ExecutionService.updateCampaign(campaign_updated);

            expect(angular.equals(ExecutionService.campaign, campaign_updated)).toBe(true);
        });
    });

    describe("viewTestExecution() -", function () {
        beforeEach(() => {
            editor = {
                on: jest.fn(),
                destroy: jest.fn(),
                showNotification: jest.fn(),
            };

            let instance = {
                execution_4: "oui",
            };

            ckeditorInlineSpy = jest.fn(() => editor);
            ckeditorRemoveSpy = jest.fn(() => "");
            setAttributeSpy = jest.spyOn(document, "getElementById");
            ckeditor_field = document.createElement("ckeditor");
            setAttributeSpy.mockReturnValue(ckeditor_field);
            CKEDITOR.inline = ckeditorInlineSpy;
            CKEDITOR.instances = instance;
            CKEDITOR.remove = ckeditorRemoveSpy;
        });
        it("Given that executions with no users on, when I user views a test, then there is user on", function () {
            var executions = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                },
            };

            var user = {
                id: 101,
                real_name: "Test",
                avatar_url: "url",
                score: 0,
            };

            var results = [user];

            ExecutionService.executions = executions;
            ExecutionService.viewTestExecution(4, user);

            expect(ckeditorInlineSpy).toHaveBeenCalled();
            expect(ckeditorRemoveSpy).toHaveBeenCalled();

            expect(ExecutionService.executions[4].viewed_by).toEqual(results);
        });

        it("Given that executions with user_one on, when I user_two views a test, then there is user_one and user_two on", function () {
            var user_one = {
                id: 101,
                real_name: "Test",
                avatar_url: "url",
                uuid: "456",
                score: 0,
            };

            var user_two = {
                id: 102,
                real_name: "Test",
                avatar_url: "url",
                uuid: "123",
                score: 0,
            };

            var executions = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                    viewed_by: [user_one],
                },
            };

            var results = [user_one, user_two];

            ExecutionService.executions = executions;
            ExecutionService.viewTestExecution(4, user_two);

            expect(ckeditorInlineSpy).toHaveBeenCalled();
            expect(ExecutionService.executions[4].viewed_by).toEqual(results);
        });

        it("Given that executions with user_one on, when I user_one views a test, then there is twice user_one on but once on campaign", function () {
            var user_one = {
                id: 101,
                real_name: "Test",
                avatar_url: "url",
                uuid: "456",
            };

            var user_one_bis = {
                id: 101,
                real_name: "Test",
                avatar_url: "url",
                uuid: "123",
            };

            var executions = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                    viewed_by: [user_one],
                },
            };

            var results = [user_one, user_one_bis];

            ExecutionService.executions = executions;
            ExecutionService.viewTestExecution(4, user_one_bis);

            expect(ckeditorInlineSpy).toHaveBeenCalled();
            expect(ExecutionService.executions[4].viewed_by).toEqual(results);
        });
    });

    describe("removeViewTestExecution() -", function () {
        it("Given that executions with two users on, when I remove view of user_one, then there is only user_two on", function () {
            var user_one = {
                id: 101,
                real_name: "Test",
                avatar_url: "url",
            };

            var user_two = {
                id: 102,
                real_name: "Test",
                avatar_url: "url",
            };

            var executions = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                    viewed_by: [user_one, user_two],
                },
            };

            var results = [user_two];

            ExecutionService.executions = executions;
            ExecutionService.removeViewTestExecution(4, user_one);

            expect(ExecutionService.executions[4].viewed_by).toEqual(results);
        });
    });

    describe("removeViewTestExecutionByUUID() -", function () {
        it("Given that executions with two users on, when I remove by user uuid, then the corresponding user is removed", function () {
            var user_one = {
                id: 101,
                real_name: "Test",
                avatar_url: "url",
                uuid: "123",
            };

            var user_two = {
                id: 102,
                real_name: "Test",
                avatar_url: "url",
                uuid: "456",
            };

            var executions = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                    viewed_by: [user_one, user_two],
                },
                5: {
                    id: 5,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                    viewed_by: [user_one, user_two],
                },
            };

            var results = [user_one];
            var result_presences_on_campaign = [user_one, user_two];

            ExecutionService.executions = executions;
            ExecutionService.presences_on_campaign = [user_one, user_two];
            ExecutionService.removeViewTestExecutionByUUID("456");

            expect(ExecutionService.executions[4].viewed_by).toEqual(results);
            expect(ExecutionService.executions[5].viewed_by).toEqual(results);
            expect(ExecutionService.presences_on_campaign).toEqual(result_presences_on_campaign);
        });
    });

    describe("removeAllViewTestExecution() -", function () {
        it("Given that executions with two users on, when I remove all views, then there is nobody on executions", function () {
            var user_one = {
                id: 101,
                real_name: "Test",
                avatar_url: "url",
            };

            var user_two = {
                id: 102,
                real_name: "Test",
                avatar_url: "url",
            };

            var executions = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                    viewed_by: [user_one, user_two],
                },
                5: {
                    id: 5,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                    viewed_by: [user_one, user_two],
                },
            };

            var results = [];

            ExecutionService.executions = executions;
            ExecutionService.removeAllViewTestExecution();

            expect(ExecutionService.executions[4].viewed_by).toEqual(results);
            expect(ExecutionService.executions[5].viewed_by).toEqual(results);
        });
    });

    describe("removeAllPresencesOnCampaign() -", function () {
        it("Given that executions with user_two on, when I remove all from campaign, then there is nobody on campaign", function () {
            var user_one = {
                id: 101,
                real_name: "Test",
                avatar_url: "url",
            };

            var user_two = {
                id: 102,
                real_name: "Test",
                avatar_url: "url",
            };

            ExecutionService.presences_on_campaign = [user_one, user_two];
            ExecutionService.removeAllPresencesOnCampaign(user_one);

            expect(ExecutionService.presences_on_campaign).toEqual([]);
        });
    });

    describe("updatePresencesOnCampaign() -", function () {
        it("Given that executions with user_one on, when I update user_one on campaign, then the score is updated", function () {
            var user_one = {
                id: 101,
                real_name: "Test",
                avatar_url: "url",
                score: 0,
            };

            var executions = {
                5: {
                    id: 5,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                        automated_tests: "",
                    },
                    status: "passed",
                    previous_result: {
                        submitted_by: user_one,
                    },
                    viewed_by: [user_one],
                },
            };

            var user_one_result = {
                id: 101,
                real_name: "Test",
                avatar_url: "url",
                score: 1,
            };

            ExecutionService.executions = executions;
            ExecutionService.presences_on_campaign = [user_one];
            ExecutionService.updatePresencesOnCampaign();

            expect(ExecutionService.presences_on_campaign[0]).toEqual(user_one_result);
        });

        it("Given that presences on campaign, when I add user_two with a score, then user_two is on campaign", function () {
            var user_one = {
                id: 101,
                real_name: "Test",
                avatar_url: "url",
                score: 1,
            };

            var user_two = {
                id: 102,
                real_name: "Test",
                avatar_url: "url",
                score: 1,
            };

            var presences_on_campaign = [user_one];

            var results = [user_one, user_two];

            ExecutionService.executions = [
                {
                    status: "failed",
                    previous_result: {
                        submitted_by: user_one,
                    },
                    definition: {
                        automated_tests: "",
                    },
                },
                {
                    status: "passed",
                    previous_result: {
                        submitted_by: user_two,
                    },
                    definition: {
                        automated_tests: "",
                    },
                },
            ];
            ExecutionService.presences_on_campaign = presences_on_campaign;
            ExecutionService.updatePresencesOnCampaign();

            expect(ExecutionService.presences_on_campaign).toEqual(results);
        });

        it("Given that presences on campaign, when I add user_two with no score, then user_two is on campaign with score 0", function () {
            var user_one = {
                id: 101,
                real_name: "Test",
                avatar_url: "url",
                score: 1,
            };

            var user_two = {
                id: 102,
                real_name: "Test",
                avatar_url: "url",
                score: 1,
            };

            var presences_on_campaign = [user_one];

            var results = [user_one, user_two];

            ExecutionService.executions = [
                {
                    status: "failed",
                    previous_result: {
                        submitted_by: user_one,
                    },
                    definition: {
                        automated_tests: "",
                    },
                },
                {
                    status: "passed",
                    previous_result: {
                        submitted_by: user_two,
                    },
                    definition: {
                        automated_tests: "",
                    },
                },
            ];
            ExecutionService.presences_on_campaign = presences_on_campaign;
            ExecutionService.updatePresencesOnCampaign();

            expect(ExecutionService.presences_on_campaign).toEqual(results);
        });
    });

    describe("displayPresencesForAllExecutions() -", function () {
        beforeEach(() => {
            editor = {
                on: jest.fn(),
                destroy: jest.fn(),
                showNotification: jest.fn(),
            };
            ckeditorInlineSpy = jest.fn(() => editor);
            setAttributeSpy = jest.spyOn(document, "getElementById");
            ckeditor_field = document.createElement("ckeditor");
            setAttributeSpy.mockReturnValue(ckeditor_field);
            CKEDITOR.inline = ckeditorInlineSpy;
        });
        it("Given that executions, when I display all users, then there users are on the associate execution", function () {
            var user_one = {
                id: "101",
                real_name: "name",
                avatar_url: "avatar",
                uuid: "1234",
            };

            var user_two = {
                id: "102",
                real_name: "name",
                avatar_url: "avatar",
                uuid: "4567",
            };

            var presences = {
                4: [
                    {
                        id: "101",
                        real_name: "name",
                        avatar_url: "avatar",
                        uuid: "1234",
                    },
                ],
                5: [
                    {
                        id: "102",
                        real_name: "name",
                        avatar_url: "avatar",
                        uuid: "4567",
                    },
                ],
            };

            var executions = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                },
                5: {
                    id: 5,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                },
            };

            var results = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                    uploaded_files_through_text_field: [],
                    viewed_by: [user_one],
                },
                5: {
                    id: 5,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                    uploaded_files_through_text_field: [],
                    viewed_by: [user_two],
                },
            };

            ExecutionService.executions = executions;
            ExecutionService.executions_loaded = true;
            ExecutionService.presences_loaded = true;
            ExecutionService.presences_by_execution = presences;
            ExecutionService.displayPresencesForAllExecutions();

            expect(ckeditorInlineSpy).toHaveBeenCalled();
            expect(ExecutionService.executions).toEqual(results);
        });
    });

    describe("displayPresencesByExecution() -", function () {
        it("Given that executions, when I display all users on one execution, then there users are on", function () {
            var user_one = {
                id: "101",
                real_name: "name",
                avatar_url: "avatar",
                uuid: "1234",
            };

            var user_two = {
                id: "102",
                real_name: "name",
                avatar_url: "avatar",
                uuid: "4567",
            };

            var executions = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                },
            };

            var presences = [user_one, user_two];

            var results = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "testmanagement_definitions/3",
                    },
                    viewed_by: [user_one, user_two],
                },
            };

            ExecutionService.executions = executions;
            ExecutionService.displayPresencesByExecution(4, presences);

            expect(ExecutionService.executions).toEqual(results);
        });
    });

    describe("addArtifactLink", () => {
        it("Given an execution id and an artifact to link to it, then the artifact will be added to the execution's linked_bugs", () => {
            let execution = {
                id: 74,
                linked_bugs: [{ id: 38, title: "thanan" }],
            };
            const artifact_to_link = { id: 88, title: "paragraphically" };
            ExecutionService.executions[74] = execution;

            ExecutionService.addArtifactLink(execution.id, artifact_to_link);

            expect(execution.linked_bugs).toEqual([{ id: 38, title: "thanan" }, artifact_to_link]);
        });
    });

    describe("updateExecutionAttachment", () => {
        it("Given an execution, an attachment id and attributes to update, Then it updates the right attachment", () => {
            const execution = {
                id: 74,
                uploaded_files_through_attachment_area: [
                    { id: 105, filename: "bug.png", progress: 0 },
                ],
            };

            ExecutionService.updateExecutionAttachment(execution, 105, {
                progress: 75,
                filename: "bug_1.png",
            });

            expect(execution.uploaded_files_through_attachment_area).toEqual([
                { id: 105, filename: "bug_1.png", progress: 75 },
            ]);
        });
    });

    describe("removeFileUploadedThroughAttachmentArea()", () => {
        it("Given an execution and an attachment id, then it should remove it from the execution uploaded attachments", () => {
            const execution = {
                id: 74,
                uploaded_files_through_attachment_area: [{ id: 105 }],
            };

            ExecutionService.removeFileUploadedThroughAttachmentArea(execution, 105);

            expect(execution.uploaded_files_through_attachment_area).toHaveLength(0);
        });
    });

    describe("addFileToDeletedFiles", () => {
        it("should add file to deleted files", () => {
            const execution = {
                removed_files: [],
            };
            ExecutionService.addFileToDeletedFiles(execution, { id: 666, name: "bug.png" });
            expect(execution.removed_files).toHaveLength(1);
        });
    });

    describe("removeFileFromDeletedFiles", () => {
        it("should remove file to deleted files", () => {
            const execution = {
                removed_files: [{ id: 666, name: "bug.png" }],
            };
            ExecutionService.removeFileFromDeletedFiles(execution, { id: 666, name: "bug.png" });
            expect(execution.removed_files).toHaveLength(0);
        });
    });

    describe("getFilesIdToRemove", () => {
        it("should return all removed files ids", () => {
            const execution = {
                removed_files: [
                    { id: 666, name: "bug.png" },
                    { id: 18, name: "bug_2.png" },
                ],
            };
            const ids = ExecutionService.getFilesIdToRemove(execution);
            expect(ids).toEqual([666, 18]);
        });
    });

    describe("clearRemovedFiles", () => {
        it("should clear removed files ids", () => {
            const files = [
                { id: 666, name: "bug.png", is_deleted: true },
                { id: 18, name: "bug_2.png", is_deleted: true },
            ];
            const execution = {
                removed_files: files,
            };
            ExecutionService.clearRemovedFiles(execution);
            expect(execution.removed_files).toEqual([]);
            expect(files[0].is_deleted).toBeFalsy();
            expect(files[1].is_deleted).toBeFalsy();
        });

        describe("hasFileBeingUploaded", () => {
            it("should return true if files are uploading", function () {
                const execution = {
                    uploaded_files_through_attachment_area: [{ id: 666, progress: 60 }],
                };
                expect(ExecutionService.hasFileBeingUploaded(execution)).toBe(true);
            });
            it("should return false if there are no files while uploading", function () {
                const execution = {
                    uploaded_files_through_attachment_area: [],
                };
                expect(ExecutionService.hasFileBeingUploaded(execution)).toBe(false);
            });
            it("should return false when all files are uploaded", function () {
                const execution = {
                    uploaded_files_through_attachment_area: [{ id: 666, progress: 100 }],
                };
                expect(ExecutionService.hasFileBeingUploaded(execution)).toBe(false);
            });
        });
    });

    describe("getUploadedFilesThroughAttachmentAreaIds()", () => {
        it("should return the ids of attachments successfully uploaded", () => {
            const execution = {
                uploaded_files_through_attachment_area: [
                    { id: 105, progress: 25, upload_error_message: "" },
                    { id: 106, progress: 100, upload_error_message: "Upload is fucked up" },
                    { id: 107, progress: 100, upload_error_message: "" },
                ],
            };

            expect(ExecutionService.getUploadedFilesThroughAttachmentAreaIds(execution)).toEqual([
                107,
            ]);
        });
    });

    describe("doesFileAlreadyExistInUploadedAttachments", () => {
        it("returns true when a file with the same id is already in the list, false otherwise", () => {
            const execution = {
                uploaded_files_through_attachment_area: [
                    { id: 101, filename: "bug_1.png" },
                    { id: 102, filename: "bug_2.png" },
                ],
            };

            expect(
                ExecutionService.doesFileAlreadyExistInUploadedAttachments(execution, { id: 101 }),
            ).toBe(true);
            expect(
                ExecutionService.doesFileAlreadyExistInUploadedAttachments(execution, { id: 103 }),
            ).toBe(false);
        });
    });
});
