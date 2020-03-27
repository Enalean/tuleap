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

import execution_module from "./execution-collection.js";
import angular from "angular";
import "angular-mocks";
import { createAngularPromiseWrapper } from "../../../../../../tests/jest/angular-promise-wrapper.js";

describe("ExecutionRestService", () => {
    let mockBackend, wrapPromise, ExecutionRestService, SharedPropertiesService;
    const UUID = "123";

    beforeEach(() => {
        angular.mock.module(execution_module);

        let $rootScope;
        angular.mock.inject(function (
            $httpBackend,
            _$rootScope_,
            _ExecutionRestService_,
            _SharedPropertiesService_
        ) {
            mockBackend = $httpBackend;
            $rootScope = _$rootScope_;
            ExecutionRestService = _ExecutionRestService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        jest.spyOn(SharedPropertiesService, "getUUID").mockReturnValue(UUID);

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    afterEach(() => {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    it("getRemoteExecutions()", async () => {
        const response = [
            {
                id: 4,
            },
            {
                id: 2,
            },
        ];

        mockBackend
            .expectGET(
                "/api/v1/testmanagement_campaigns/1/testmanagement_executions?limit=10&offset=0"
            )
            .respond(JSON.stringify(response));

        const promise = ExecutionRestService.getRemoteExecutions(1, 10, 0);
        mockBackend.flush();

        const executions = await wrapPromise(promise);
        expect(executions.results.length).toEqual(2);
    });

    it("postTestExecution()", async () => {
        const execution = {
            id: 4,
            status: "notrun",
        };

        mockBackend.expectPOST("/api/v1/testmanagement_executions").respond(execution);

        const promise = ExecutionRestService.postTestExecution("notrun", "CentOS 5 - PHP 5.1");

        mockBackend.flush();

        const execution_updated = await wrapPromise(promise);
        expect(execution_updated.id).toBeDefined();
    });

    it("putTestExecution()", async () => {
        const execution = {
            id: 4,
            status: "passed",
            previous_result: {
                result: "",
                status: "notrun",
            },
        };

        mockBackend
            .expectPUT("/api/v1/testmanagement_executions/4?results=nothing&status=passed&time=1")
            .respond(execution);

        const promise = ExecutionRestService.putTestExecution(4, "passed", 1, "nothing");

        mockBackend.flush();

        const execution_updated = await wrapPromise(promise);
        expect(execution_updated.id).toBeDefined();
    });

    it("changePresenceOnTestExecution()", async () => {
        mockBackend.expectPATCH("/api/v1/testmanagement_executions/9/presences").respond();

        const promise = ExecutionRestService.changePresenceOnTestExecution(9, 4);

        mockBackend.flush();

        const response = await wrapPromise(promise);
        expect(response.status).toEqual(200);
    });

    it("linkIssue()", async () => {
        const issueId = 400;
        const execution = {
            id: 100,
            previous_result: {
                result: "Something wrong",
            },
            definition: {
                summary: "test summary",
                description: "test description",
            },
        };

        const expectedBody = new RegExp(
            execution.definition.summary + ".*" + execution.definition.description
        );
        const matchPayload = {
            id: issueId,
            comment: {
                body: "MATCHING TEST SUMMARY + DESCRIPTION",
                format: "html",
            },
            test: function (data) {
                const payload = JSON.parse(data);
                return (
                    payload.issue_id === issueId &&
                    expectedBody.test(payload.comment.body) &&
                    payload.comment.format === "html"
                );
            },
        };
        mockBackend
            .expectPATCH("/api/v1/testmanagement_executions/100/issues", matchPayload)
            .respond();

        const promise = ExecutionRestService.linkIssue(issueId, execution);

        mockBackend.flush();

        const response = await wrapPromise(promise);
        expect(response.status).toEqual(200);
    });

    it("getLinkedArtifacts()", async () => {
        const linked_issues = [
            {
                id: 219,
                xref: "bug #219",
                title: "mascleless dollhouse",
                tracker: { id: 23 },
            },
            {
                id: 402,
                xref: "bug #402",
                title: "sugar candescent",
                tracker: { id: 23 },
            },
        ];

        mockBackend
            .expectGET(
                "/api/v1/artifacts/148/linked_artifacts?direction=forward&limit=10&nature=&offset=0"
            )
            .respond(
                angular.toJson({
                    collection: linked_issues,
                }),
                {
                    "X-Pagination-Size": 2,
                }
            );

        const test_execution = { id: 148 };
        const promise = ExecutionRestService.getLinkedArtifacts(test_execution, 10, 0);
        mockBackend.flush();

        const result = await wrapPromise(promise);
        expect(result).toEqual({
            collection: linked_issues,
            total: 2,
        });
    });

    it("getArtifactById()", async () => {
        const artifact = {
            id: 61,
            xref: "bug #61",
            title: "intercloud haustorium",
            tracker: { id: 4 },
        };
        mockBackend.expectGET("/api/v1/artifacts/61").respond(angular.toJson(artifact));

        const promise = ExecutionRestService.getArtifactById(61);
        mockBackend.flush();

        const result = await wrapPromise(promise);
        expect(result).toEqual(artifact);
    });

    describe("updateStepStatus()", () => {
        it("Given an execution id, a step id and a status, then the REST route will be called", () => {
            const test_execution = { id: 26 };
            const step_id = 96;
            const status = "failed";
            mockBackend
                .expectPATCH(
                    "/api/v1/testmanagement_executions/26",
                    {
                        steps_results: [{ step_id, status }],
                    },
                    (headers) => headers["X-Client-UUID"] === UUID
                )
                .respond(200);

            const promise = ExecutionRestService.updateStepStatus(test_execution, step_id, status);
            mockBackend.flush();

            return wrapPromise(promise);
        });

        it("Given there is a REST error, then a promise will be rejected with the error message", () => {
            const test_execution = { id: 21 };
            const step_id = 38;
            const status = "blocked";
            mockBackend.whenPATCH("/api/v1/testmanagement_executions/21").respond(403, {
                error: { message: "This user cannot update the execution" },
            });

            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = ExecutionRestService.updateStepStatus(
                test_execution,
                step_id,
                status
            ).catch((error) => {
                expect(error).toEqual("This user cannot update the execution");
            });
            mockBackend.flush();
            return wrapPromise(promise);
        });
    });
});
