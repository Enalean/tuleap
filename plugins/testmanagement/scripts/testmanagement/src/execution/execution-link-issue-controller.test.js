/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import execution_module from "./execution.js";
import angular from "angular";
import "angular-mocks";

import BaseController from "./execution-link-issue-controller.js";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

describe("ExecutionLinkIssueController", () => {
    let $q,
        $scope,
        wrapPromise,
        ExecutionLinkIssueController,
        ExecutionRestService,
        modal_instance,
        modal_model,
        modal_callback,
        SharedPropertiesService;

    const issue_tracker_id = 8;

    beforeEach(() => {
        angular.mock.module(execution_module);

        let $controller, $rootScope;

        angular.mock.inject(
            function (
                _$controller_,
                _$q_,
                _$rootScope_,
                _ExecutionRestService_,
                _SharedPropertiesService_,
            ) {
                $controller = _$controller_;
                $q = _$q_;
                $rootScope = _$rootScope_;
                ExecutionRestService = _ExecutionRestService_;
                SharedPropertiesService = _SharedPropertiesService_;
            },
        );

        wrapPromise = createAngularPromiseWrapper($rootScope);
        $scope = $rootScope.$new();

        modal_instance = {
            tlp_modal: {
                hide: jest.fn(),
            },
        };

        modal_model = {
            test_execution: {
                id: 21,
                definition: {
                    summary: "tempestuous",
                },
            },
        };

        modal_callback = jest.fn();

        jest.spyOn(SharedPropertiesService, "getIssueTrackerId").mockReturnValue(issue_tracker_id);
        jest.spyOn(SharedPropertiesService, "getIssueTrackerConfig").mockReturnValue({
            xref_color: "flamingo_pink",
        });

        ExecutionLinkIssueController = $controller(BaseController, {
            $scope,
            ExecutionRestService,
            modal_instance,
            modal_model,
            modal_callback,
            SharedPropertiesService,
        });
    });

    describe("validateIssueIsNotAlreadyLinked", () => {
        beforeEach(() => {
            modal_model.test_execution.linked_bugs = [
                { id: 39, title: "disklike", xref: "bugs #39" },
                { id: 80, title: "schoolless", xref: "bugs #80" },
            ];
        });

        it("Given an artifact id that is already linked to the execution, then it will return false", () => {
            const result = ExecutionLinkIssueController.validateIssueIsNotAlreadyLinked("", "80");

            expect(result).toBe(false);
        });

        it("Given an artifact id that is not already linked to the execution, then it will return true", () => {
            const result = ExecutionLinkIssueController.validateIssueIsNotAlreadyLinked("", "66");

            expect(result).toBe(true);
        });
    });

    describe("validateIssueId()", () => {
        it(`Given that the linking modal was initialized,
            when I enter a bug artifact id,
            then it will be valid and will be attached to the controller`, async () => {
            const artifact = {
                id: 52,
                title: "nonreceipt aroxyl",
                xref: "bug #52",
                tracker: {
                    id: issue_tracker_id,
                },
            };
            jest.spyOn(ExecutionRestService, "getArtifactById").mockReturnValue($q.when(artifact));

            var promise = ExecutionLinkIssueController.validateIssueId("", "52");

            await expect(wrapPromise(promise)).resolves.toBe(true);
            expect(ExecutionRestService.getArtifactById).toHaveBeenCalledWith("52");
            expect(ExecutionLinkIssueController.issue_artifact).toBe(artifact);
            expect(ExecutionLinkIssueController.issue_artifact.tracker.color_name).toBe(
                "flamingo_pink",
            );
        });

        it(`Given that the linking modal was initialized,
            when I enter an artifact id that isn't a bug,
            then it will not be valid and the promise will be rejected`, () => {
            const artifact = {
                id: 17,
                title: "nonprejudicial Elodeaceae",
                xref: "story #17",
                tracker: {
                    id: 10,
                },
            };
            jest.spyOn(ExecutionRestService, "getArtifactById").mockReturnValue($q.when(artifact));

            expect.assertions(2);
            // eslint-disable-next-line jest/valid-expect-in-promise
            var promise = ExecutionLinkIssueController.validateIssueId("", "17").catch(() => {
                // eslint-disable-next-line jest/no-conditional-expect
                expect(ExecutionRestService.getArtifactById).toHaveBeenCalledWith("17");
                // eslint-disable-next-line jest/no-conditional-expect
                expect(ExecutionLinkIssueController.issue_artifact).toBeNull();
            });

            return wrapPromise(promise);
        });
    });

    describe("linkIssue() -", () => {
        it("Given I had selected an issue, when I link it, then ExecutionRestService will be called, the modal will be hidden and the modal's callback will be called with the issue", () => {
            const issue_artifact = {
                id: 39,
                title: "chromatinic duvet",
                xref: "bug #39",
                tracker: {
                    id: issue_tracker_id,
                    color_name: "fiesta-red",
                },
            };
            ExecutionLinkIssueController.issue_artifact = issue_artifact;
            ExecutionLinkIssueController.issue.id = issue_artifact.id;
            jest.spyOn(ExecutionRestService, "linkIssue").mockReturnValue($q.when());

            ExecutionLinkIssueController.linkIssue();
            expect(ExecutionLinkIssueController.linking_in_progress).toBe(true);
            $scope.$apply();

            expect(ExecutionRestService.linkIssue).toHaveBeenCalledWith(
                issue_artifact.id,
                modal_model.test_execution,
            );
            expect(modal_instance.tlp_modal.hide).toHaveBeenCalled();
            expect(modal_callback).toHaveBeenCalledWith(issue_artifact);
            expect(ExecutionLinkIssueController.linking_in_progress).toBe(false);
        });
    });
});
