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

export default DropZoneController;

DropZoneController.$inject = ["$element", "$rootScope"];

function DropZoneController($element, $rootScope) {
    const self = this;

    Object.assign(self, {
        $onInit,
        $onDestroy,
        drop_zone: null,
        has_files_being_dragged_over: false,
    });

    function $onInit() {
        self.drop_zone = $element[0];

        if (!self.drop_zone) {
            return;
        }

        self.drop_zone.addEventListener("dragover", onDragOver);
        self.drop_zone.addEventListener("dragleave", onDragLeave);
        self.drop_zone.addEventListener("drop", onDrop, true);

        $rootScope.$on("drop-zone-active", startDropZoneHighlight);
        $rootScope.$on("drop-zone-inactive", stopDropZoneHighlight);
    }

    function $onDestroy() {
        if (!self.drop_zone) {
            return;
        }

        self.drop_zone.removeEventListener("dragover", onDragOver);
        self.drop_zone.removeEventListener("dragleave", onDragLeave);
        self.drop_zone.removeEventListener("drop", onDrop);
    }

    function onDragOver(event) {
        if (self.executionAttachmentsDropZoneAllowDnd === false) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (self.has_files_being_dragged_over) {
            return;
        }

        startDropZoneHighlight();
        $rootScope.$emit("drop-zone-active");
    }

    function onDragLeave(event) {
        if (self.executionAttachmentsDropZoneAllowDnd === false) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (!self.has_files_being_dragged_over) {
            return;
        }

        stopDropZoneHighlight();
        $rootScope.$emit("drop-zone-inactive");
    }

    function onDrop(event) {
        stopDropZoneHighlight();
        $rootScope.$emit("drop-zone-inactive");

        const files = Array.from(event.dataTransfer.files);
        if (event.target.closest(".current-test-comment")) {
            $rootScope.$emit("execution-attachments-dropped", {
                files: files.filter((file) => !file.type.includes("image/")),
            });

            return;
        }

        event.preventDefault();
        event.stopPropagation();

        $rootScope.$emit("execution-attachments-dropped", { files });
    }

    function startDropZoneHighlight() {
        self.has_files_being_dragged_over = true;
        self.drop_zone.classList.add("drop-zone-highlighted");
    }

    function stopDropZoneHighlight() {
        self.has_files_being_dragged_over = false;
        self.drop_zone.classList.remove("drop-zone-highlighted");
    }
}
