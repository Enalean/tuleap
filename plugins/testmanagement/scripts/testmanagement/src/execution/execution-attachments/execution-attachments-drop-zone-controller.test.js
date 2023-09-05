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
import DropZoneController from "./execution-attachments-drop-zone-controller.js";

describe("execution-attachments-drop-zone", () => {
    let $controller, $root_scope, $element;

    function getDropZoneController(is_dnd_allowed = true) {
        return $controller(
            DropZoneController,
            {
                $element,
                $root_scope,
            },
            {
                executionAttachmentsDropZoneAllowDnd: is_dnd_allowed,
            },
        );
    }

    beforeEach(() => {
        angular.mock.module(execution_module);
        angular.mock.inject((_$controller_, $rootScope) => {
            $root_scope = $rootScope;
            $controller = _$controller_;
            $element = angular.element(`
                <div>
                    <div class="current-test-comment"></div>
                </div>
            `);
        });
    });

    describe("$onInit()", () => {
        it("Should listen dnd events", () => {
            const drop_zone = getDropZoneController();

            jest.spyOn($element[0], "addEventListener");
            jest.spyOn($root_scope, "$on");

            drop_zone.$onInit();

            expect($element[0].addEventListener).toHaveBeenCalledWith(
                "dragover",
                expect.any(Function),
            );
            expect($element[0].addEventListener).toHaveBeenCalledWith(
                "dragleave",
                expect.any(Function),
            );
            expect($element[0].addEventListener).toHaveBeenCalledWith(
                "drop",
                expect.any(Function),
                true,
            );

            expect($root_scope.$on).toHaveBeenCalledWith("drop-zone-active", expect.any(Function));
            expect($root_scope.$on).toHaveBeenCalledWith(
                "drop-zone-inactive",
                expect.any(Function),
            );
        });
    });

    describe("$onDestroy()", () => {
        it("should remove listeners on dnd events", () => {
            const drop_zone = getDropZoneController();
            drop_zone.$onInit();

            jest.spyOn($element[0], "removeEventListener");

            drop_zone.$onDestroy();

            expect($element[0].removeEventListener).toHaveBeenCalledWith(
                "dragover",
                expect.any(Function),
            );
            expect($element[0].removeEventListener).toHaveBeenCalledWith(
                "dragleave",
                expect.any(Function),
            );
            expect($element[0].removeEventListener).toHaveBeenCalledWith(
                "drop",
                expect.any(Function),
            );
        });
    });

    describe("drop zone highlight", () => {
        let drop_zone, drop_zone_element, dragover, dragleave, drop;

        beforeEach(() => {
            drop_zone = getDropZoneController();
            drop_zone.$onInit();

            drop_zone_element = $element[0];

            dragover = new Event("dragover");
            jest.spyOn(dragover, "preventDefault");
            jest.spyOn(dragover, "stopPropagation");

            dragleave = new Event("dragleave");
            jest.spyOn(dragleave, "preventDefault");
            jest.spyOn(dragleave, "stopPropagation");

            drop = new Event("drop", {});
            jest.spyOn(drop, "preventDefault");
            jest.spyOn(drop, "stopPropagation");

            jest.spyOn($root_scope, "$emit");
        });

        describe("dragover", () => {
            it("highlights the drop zone when something is being dragged over it", () => {
                drop_zone_element.dispatchEvent(dragover);

                expect(dragover.preventDefault).toHaveBeenCalled();
                expect(dragover.stopPropagation).toHaveBeenCalled();

                expect(drop_zone.has_files_being_dragged_over).toBe(true);
                expect(drop_zone_element.classList.contains("drop-zone-highlighted")).toBe(true);

                expect($root_scope.$emit).toHaveBeenCalledWith("drop-zone-active");
            });

            it("does not emit drop-zone-active when the drop zone is already highlighted", () => {
                drop_zone_element.dispatchEvent(dragover);
                drop_zone_element.dispatchEvent(dragover);

                expect($root_scope.$emit.mock.calls).toHaveLength(1);
            });

            it("does not highlight the drop zone when the dnd is not allowed", () => {
                drop_zone.executionAttachmentsDropZoneAllowDnd = false;

                drop_zone_element.dispatchEvent(dragover);

                expect(dragover.preventDefault).not.toHaveBeenCalled();
                expect(dragover.stopPropagation).not.toHaveBeenCalled();

                expect(drop_zone.has_files_being_dragged_over).toBe(false);
                expect(drop_zone_element.classList.contains("drop-zone-highlighted")).toBe(false);

                expect($root_scope.$emit).not.toHaveBeenCalled();
            });
        });

        describe("dragleave", () => {
            it("removes the highlight on the drop zone when something has been dragged away from it", () => {
                drop_zone_element.dispatchEvent(dragover);
                drop_zone_element.dispatchEvent(dragleave);

                expect(dragleave.preventDefault).toHaveBeenCalled();
                expect(dragleave.stopPropagation).toHaveBeenCalled();

                expect(drop_zone.has_files_being_dragged_over).toBe(false);
                expect(drop_zone_element.classList.contains("drop-zone-highlighted")).toBe(false);

                expect($root_scope.$emit).toHaveBeenCalledWith("drop-zone-inactive");
            });

            it("does not emit drop-zone-inactive when the highlight on the drop zone has already been removed", () => {
                drop_zone_element.dispatchEvent(dragover);
                drop_zone_element.dispatchEvent(dragleave);
                drop_zone_element.dispatchEvent(dragleave);

                expect($root_scope.$emit.mock.calls).toHaveLength(2);
            });

            it("does nothing when the dnd is not allowed", () => {
                drop_zone.executionAttachmentsDropZoneAllowDnd = false;

                drop_zone_element.dispatchEvent(dragleave);

                expect(dragleave.preventDefault).not.toHaveBeenCalled();
                expect(dragleave.stopPropagation).not.toHaveBeenCalled();

                expect($root_scope.$emit).not.toHaveBeenCalled();
            });
        });

        describe("drop", () => {
            it("removes the highlight and broadcasts the dropped files", () => {
                const files = [{ name: "bug_1.png" }, { name: "bug_2.png" }];
                drop.dataTransfer = { files };

                drop_zone_element.dispatchEvent(dragover);
                drop_zone_element.dispatchEvent(drop);

                expect(drop.preventDefault).toHaveBeenCalled();
                expect(drop.stopPropagation).toHaveBeenCalled();

                expect(drop_zone.has_files_being_dragged_over).toBe(false);
                expect(drop_zone_element.classList.contains("drop-zone-highlighted")).toBe(false);

                expect($root_scope.$emit).toHaveBeenCalledWith("drop-zone-inactive");
                expect($root_scope.$emit).toHaveBeenCalledWith("execution-attachments-dropped", {
                    files,
                });
            });

            it("Given some files have been dropped in the comment box, Then it uploads only the non-image files and let the event propagate", () => {
                const comment_box = $element[0].querySelector(".current-test-comment");
                const files = [
                    { name: "bug_1.png", type: "image/png" },
                    { name: "AC.jpeg", type: "image/jpeg" },
                    { name: "error.txt", type: "text/plain" },
                ];

                drop.dataTransfer = { files };

                comment_box.dispatchEvent(drop);

                expect(drop.preventDefault).not.toHaveBeenCalled();
                expect(drop.stopPropagation).not.toHaveBeenCalled();

                expect($root_scope.$emit).toHaveBeenCalledWith("drop-zone-inactive");
                expect($root_scope.$emit).toHaveBeenCalledWith("execution-attachments-dropped", {
                    files: [{ name: "error.txt", type: "text/plain" }],
                });
            });
        });

        describe("drop-zone-active/drop-zone-inactive", () => {
            it("highlights the drop zone when the event drop-zone-active has been received", () => {
                $root_scope.$emit("drop-zone-active");

                expect(drop_zone.has_files_being_dragged_over).toBe(true);
                expect(drop_zone_element.classList.contains("drop-zone-highlighted")).toBe(true);
            });

            it("remove the highlight on the drop zone when the event drop-zone-inactive has been received", () => {
                $root_scope.$emit("drop-zone-active");
                $root_scope.$emit("drop-zone-inactive");

                expect(drop_zone.has_files_being_dragged_over).toBe(false);
                expect(drop_zone_element.classList.contains("drop-zone-highlighted")).toBe(false);
            });
        });
    });
});
