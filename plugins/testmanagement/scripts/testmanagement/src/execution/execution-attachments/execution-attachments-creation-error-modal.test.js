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
import * as tlp from "tlp";

jest.mock("tlp");

describe("execution-attachments-creation-error-modal", () => {
    let controller,
        $scope,
        $element,
        errors = [],
        tlp_error_modal;

    beforeEach(() => {
        angular.mock.module(execution_module);
        angular.mock.inject(($componentController, $rootScope) => {
            $scope = $rootScope.$new();
            $element = angular.element("<div></div>");

            controller = $componentController(
                "executionAttachmentsCreationErrorModal",
                { $element, $scope },
                { errors },
            );
        });

        tlp_error_modal = {
            is_shown: false,
            element: document.createElement("div"),
            destroy: jest.fn(),
            addEventListener: jest.fn().mockImplementation(function (event_name, callback) {
                callback();
            }),
            show: jest.fn().mockImplementation(function () {
                this.is_shown = !this.is_shown;
            }),
            hide: function () {
                this.element.dispatchEvent(new Event("tlp-modal-hidden"));
                this.is_shown = false;
            },
        };

        jest.spyOn(tlp, "createModal").mockReturnValue(tlp_error_modal);
    });

    describe("$onInit()", () => {
        it("should create a modal and show it when there are errors for the first time", () => {
            jest.spyOn($scope, "$emit");
            controller.$onInit();

            expect(controller.modal).toBeNull();

            errors.push({ filename: "bug_1.png", message: "File too big" });

            $scope.$digest();

            expect(controller.modal).toBe(tlp_error_modal);
            expect(tlp_error_modal.addEventListener).toHaveBeenCalledWith(
                "tlp-modal-hidden",
                expect.any(Function),
            );
            expect(tlp_error_modal.show).toHaveBeenCalled();
            expect(tlp_error_modal.is_shown).toBe(true);

            tlp_error_modal.hide();
            $scope.$digest();

            expect(tlp_error_modal.is_shown).toBe(false);
            expect($scope.$emit).toHaveBeenCalledWith(
                "user-has-closed-the-file-creation-errors-modal",
            );
        });
    });

    describe("$onDestroy()", () => {
        it("should destroy the modal when there is one", () => {
            controller.$onInit();

            errors.push({ filename: "mugshot.jpeg", message: "Face too ugly" });
            $scope.$digest();

            controller.$onDestroy();

            expect(tlp_error_modal.destroy).toHaveBeenCalled();
        });
    });
});
