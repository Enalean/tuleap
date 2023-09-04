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
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";
import * as tlp from "@tuleap/tlp-fetch";

describe("ExecutionRestService", () => {
    let wrapPromise, $q, ExecutionRestService;
    const UUID = "123";

    beforeEach(() => {
        jest.resetAllMocks();
        angular.mock.module(execution_module, function ($provide) {
            $provide.decorator("SharedPropertiesService", function ($delegate) {
                jest.spyOn($delegate, "getUUID").mockReturnValue(UUID);
                return $delegate;
            });
        });

        let $rootScope;
        angular.mock.inject(function (_$q_, _$rootScope_, _ExecutionRestService_) {
            $q = _$q_;
            $rootScope = _$rootScope_;
            $q = _$q_;
            ExecutionRestService = _ExecutionRestService_;
        });

        wrapPromise = createAngularPromiseWrapper($rootScope);
    });

    function mockFetchSuccess(spy_function, { headers, return_json } = {}) {
        spy_function.mockReturnValue(
            $q.when({
                headers,
                json: () => $q.when(return_json),
            }),
        );
    }

    function mockFetchError(spy_function, { status, statusText, error_json } = {}) {
        spy_function.mockReturnValue(
            $q.reject({
                response: {
                    status,
                    statusText,
                    json: () => $q.when(error_json),
                },
            }),
        );
    }

    const expected_headers = {
        "content-type": "application/json",
        "X-Client-UUID": UUID,
    };

    it("getRemoteExecutions()", async () => {
        const executions = [{ id: 4 }, { id: 2 }];
        const tlpGet = jest.spyOn(tlp, "get");
        mockFetchSuccess(tlpGet, {
            return_json: executions,
            headers: {
                get: () => {
                    return "2";
                },
            },
        });

        const promise = ExecutionRestService.getRemoteExecutions(1, 10, 0);

        const response = await wrapPromise(promise);
        expect(response.total).toBe("2");
        expect(response.results).toEqual(executions);
        expect(tlpGet).toHaveBeenCalledWith(
            "/api/v1/testmanagement_campaigns/1/testmanagement_executions",
            {
                params: { limit: 10, offset: 0 },
            },
        );
    });

    it("postTestExecution()", async () => {
        const tlpPost = jest.spyOn(tlp, "post");
        const execution_representation = { id: 4, status: "notrun" };
        mockFetchSuccess(tlpPost, { return_json: execution_representation });

        const promise = ExecutionRestService.postTestExecution(32, 231, "notrun");
        const new_execution = await wrapPromise(promise);

        expect(new_execution).toEqual(execution_representation);
        expect(tlpPost).toHaveBeenCalledWith("/api/v1/testmanagement_executions", {
            headers: expected_headers,
            body: JSON.stringify({
                tracker: { id: 32 },
                definition_id: 231,
                status: "notrun",
            }),
        });
    });

    it("putTestExecution()", async () => {
        const execution_representation = { id: 4, status: "passed", uploaded_file_ids: [13] };
        const tlpPutSpy = jest.spyOn(tlp, "put");
        mockFetchSuccess(tlpPutSpy, { return_json: execution_representation });

        const promise = ExecutionRestService.putTestExecution(4, "passed", "nothing", [13], [14]);
        const execution_updated = await wrapPromise(promise);

        expect(execution_updated).toEqual(execution_representation);
        expect(tlpPutSpy).toHaveBeenCalledWith("/api/v1/testmanagement_executions/4", {
            headers: expected_headers,
            body: JSON.stringify({
                status: "passed",
                uploaded_file_ids: [13],
                deleted_file_ids: [14],
                results: "nothing",
            }),
        });
    });

    describe(`updateExecutionToUseLatestVersionOfDefinition()`, () => {
        it(`will call PATCH on the execution to force it to use the latest test definition`, async () => {
            const tlpPatch = jest.spyOn(tlp, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = ExecutionRestService.updateExecutionToUseLatestVersionOfDefinition(12);
            await wrapPromise(promise);

            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/testmanagement_executions/12", {
                headers: expected_headers,
                body: JSON.stringify({ force_use_latest_definition_version: true }),
            });
        });
    });

    it("changePresenceOnTestExecution()", async () => {
        const tlpPatch = jest.spyOn(tlp, "patch");
        mockFetchSuccess(tlpPatch);

        const promise = ExecutionRestService.changePresenceOnTestExecution(9, 4);
        await wrapPromise(promise);

        expect(tlpPatch).toHaveBeenCalledWith("/api/v1/testmanagement_executions/9/presences", {
            headers: expected_headers,
            body: JSON.stringify({ uuid: UUID, remove_from: 4 }),
        });
    });

    describe(`leaveTestExecution()`, () => {
        it(`will call PATCH on the execution's presences
            and will remove the current user from the presences`, async () => {
            const tlpPatch = jest.spyOn(tlp, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = ExecutionRestService.leaveTestExecution(9);
            await wrapPromise(promise);

            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/testmanagement_executions/9/presences", {
                headers: expected_headers,
                body: JSON.stringify({ uuid: UUID, remove_from: 9 }),
            });
        });
    });

    it("getArtifactById()", async () => {
        const artifact = {
            id: 61,
            xref: "bug #61",
            title: "intercloud haustorium",
            tracker: { id: 4 },
        };
        const tlpGet = jest.spyOn(tlp, "get");
        mockFetchSuccess(tlpGet, { return_json: artifact });

        const promise = ExecutionRestService.getArtifactById(61);
        const result = await wrapPromise(promise);

        expect(result).toEqual(artifact);
        expect(tlpGet).toHaveBeenCalledWith("/api/v1/artifacts/61");
    });

    describe(`linkIssue()`, () => {
        let execution;
        beforeEach(() => {
            execution = {
                id: 100,
                previous_result: { result: "Something wrong" },
                definition: { summary: "test summary", description: "test description" },
            };
        });

        it(`will call PATCH on the execution's issues
            and will link the given issue to the execution
            and will add the given comment to the exection`, async () => {
            const issue_id = 400;
            const tlpPatch = jest.spyOn(tlp, "patch");
            mockFetchSuccess(tlpPatch);

            const expected_body_regex = new RegExp(
                execution.definition.summary + ".*" + execution.definition.description,
            );

            const promise = ExecutionRestService.linkIssue(issue_id, execution);
            await wrapPromise(promise);

            expect(tlpPatch).toHaveBeenCalledWith(
                "/api/v1/testmanagement_executions/100/issues",
                expect.anything(),
            );
            const init_argument = tlpPatch.mock.calls[0][1];
            expect(init_argument.headers).toEqual(expected_headers);
            const raw_body = JSON.parse(init_argument.body);
            expect(raw_body.issue_id).toEqual(issue_id);
            expect(raw_body.comment.format).toBe("html");
            expect(raw_body.comment.body).toMatch(expected_body_regex);
        });

        it(`when there is an error, it will parse the response's JSON a return its error property`, () => {
            const tlpPatch = jest.spyOn(tlp, "patch");
            mockFetchError(tlpPatch, { status: 403, error_json: { error: "Forbidden" } });

            expect.assertions(1);
            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = ExecutionRestService.linkIssue(123, execution).catch((error) => {
                // eslint-disable-next-line jest/no-conditional-expect
                expect(error).toBe("Forbidden");
            });

            return wrapPromise(promise);
        });
    });

    describe(`linkIssueWithoutComment()`, () => {
        it(`will call PATCH on the execution's issues
            and will link the given issue to the execution
            and will add an empty comment in text format`, async () => {
            const tlpPatch = jest.spyOn(tlp, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = ExecutionRestService.linkIssueWithoutComment(123, { id: 456 });
            await wrapPromise(promise);

            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/testmanagement_executions/456/issues", {
                headers: expected_headers,
                body: JSON.stringify({ issue_id: 123, comment: { body: "", format: "text" } }),
            });
        });

        it(`when there is an error, it will parse the response's JSON a return its error property`, () => {
            const tlpPatch = jest.spyOn(tlp, "patch");
            mockFetchError(tlpPatch, { status: 403, error_json: { error: "Forbidden" } });

            expect.assertions(1);
            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = ExecutionRestService.linkIssueWithoutComment(123, { id: 456 }).catch(
                (error) => {
                    // eslint-disable-next-line jest/no-conditional-expect
                    expect(error).toBe("Forbidden");
                },
            );

            return wrapPromise(promise);
        });
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

        const tlpGet = jest.spyOn(tlp, "get");
        mockFetchSuccess(tlpGet, {
            return_json: { collection: linked_issues },
            headers: {
                get: () => {
                    return "2";
                },
            },
        });

        const test_execution = { id: 148 };
        const promise = ExecutionRestService.getLinkedArtifacts(test_execution, 10, 0);

        const result = await wrapPromise(promise);
        expect(result).toEqual({
            collection: linked_issues,
            total: 2,
        });
        expect(tlpGet).toHaveBeenCalledWith("/api/v1/artifacts/148/linked_artifacts", {
            params: { direction: "forward", nature: "", limit: 10, offset: 0 },
        });
    });

    describe(`getExecution()`, () => {
        it(`will call GET on the testmanagement_executions and return the result`, async () => {
            const test_execution = { id: 110 };
            const tlpGet = jest.spyOn(tlp, "get");
            mockFetchSuccess(tlpGet, { return_json: test_execution });

            const promise = ExecutionRestService.getExecution(110);
            const result = await wrapPromise(promise);

            expect(tlpGet).toHaveBeenCalledWith("/api/v1/testmanagement_executions/110");
            expect(result).toEqual(test_execution);
        });
    });

    describe("updateStepStatus()", () => {
        it("Given an execution id, a step id and a status, then the REST route will be called", async () => {
            const test_execution = { id: 26 };
            const step_id = 96;
            const status = "failed";

            const tlpPatch = jest.spyOn(tlp, "patch");
            mockFetchSuccess(tlpPatch);

            const promise = ExecutionRestService.updateStepStatus(test_execution, step_id, status);
            await wrapPromise(promise);

            expect(tlpPatch).toHaveBeenCalledWith("/api/v1/testmanagement_executions/26", {
                headers: expected_headers,
                body: JSON.stringify({ steps_results: [{ step_id, status }] }),
            });
        });

        it("Given there is a REST error, then a promise will be rejected with the error message", () => {
            const test_execution = { id: 21 };
            const step_id = 38;
            const status = "blocked";
            const tlpPatch = jest.spyOn(tlp, "patch");
            mockFetchError(tlpPatch, {
                status: 403,
                error_json: { error: "This user cannot update the execution" },
            });

            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = ExecutionRestService.updateStepStatus(
                test_execution,
                step_id,
                status,
            ).catch((error) => {
                // eslint-disable-next-line jest/no-conditional-expect
                expect(error).toBe("This user cannot update the execution");
            });
            return wrapPromise(promise);
        });
    });

    describe("createFileInTestExecution()", () => {
        let test_execution, file_info;

        beforeEach(() => {
            test_execution = {
                upload_url: "/api/v1/tracker_fields/1260/file",
            };

            file_info = {
                name: "bug.png",
                file_size: 12345678910,
                file_type: "image/png",
            };
        });
        it("should create a file for the given test execution and return its representation", async () => {
            const created_file = {
                id: 1234,
                upload_href: "/upload-me.here",
                download_href: "/download-me.there",
            };

            const tlpPost = jest.spyOn(tlp, "post");
            mockFetchSuccess(tlpPost, { return_json: created_file });

            const promise = ExecutionRestService.createFileInTestExecution(
                test_execution,
                file_info,
            );
            const result = await wrapPromise(promise);

            expect(tlpPost).toHaveBeenCalledWith("/api/v1/tracker_fields/1260/file", {
                headers: expected_headers,
                body: JSON.stringify({
                    name: "bug.png",
                    file_size: 12345678910,
                    file_type: "image/png",
                }),
            });
            expect(result).toEqual(created_file);
        });

        it("should return the error sent by the backend", () => {
            const test_execution = {
                upload_url: "/api/v1/tracker_fields/1260/file",
            };

            const tlpPost = jest.spyOn(tlp, "post");
            mockFetchError(tlpPost, { status: 400, error_json: { error: "File is too big" } });

            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = ExecutionRestService.createFileInTestExecution(
                test_execution,
                file_info,
            ).catch((error) => {
                // eslint-disable-next-line jest/no-conditional-expect
                expect(error).toBe("File is too big");
            });

            return wrapPromise(promise);
        });
    });
});
