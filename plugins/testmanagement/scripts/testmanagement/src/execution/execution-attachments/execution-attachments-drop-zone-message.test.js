/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import angular from "angular";
import "angular-mocks";
import execution_module from "../execution.js";

describe("execution-attachments-drop-zone-message", () => {
    let controller, $scope, $root_scope;

    beforeEach(() => {
        angular.mock.module(execution_module);
        angular.mock.inject(
            ($componentController, $rootScope, _gettextCatalog_, _SharedPropertiesService_) => {
                $root_scope = $rootScope;
                $scope = $rootScope.$new();

                jest.spyOn(_SharedPropertiesService_, "getFileUploadMaxSize").mockReturnValue(
                    100000000,
                );
                jest.spyOn($root_scope, "$on");

                controller = $componentController("executionAttachmentsDropZoneMessage", {
                    $rootScope,
                    $scope,
                    _gettextCatalog_,
                    _SharedPropertiesService_,
                });
            },
        );

        controller.$onInit();
    });

    describe("$onInit()", () => {
        it("should listen to the drop zone activation/deactivation", () => {
            expect($root_scope.$on).toHaveBeenCalledWith("drop-zone-active", expect.any(Function));
            expect($root_scope.$on).toHaveBeenCalledWith(
                "drop-zone-inactive",
                expect.any(Function),
            );
        });
    });

    describe("show/hide", () => {
        it("should show the message when the drop zone is active", () => {
            $root_scope.$emit("drop-zone-active");

            expect(controller.is_shown).toBe(true);
        });

        it("should hide the message when the drop zone is inactive", () => {
            $root_scope.$emit("drop-zone-inactive");

            expect(controller.is_shown).toBe(false);
        });
    });

    describe("message", () => {
        it("should build a message informing the user about the max file size allowed", () => {
            expect(controller.getMessage()).toBe(
                "Drop files here to attach them to your comment (max size is 95.4 MBs).",
            );
        });
    });
});
