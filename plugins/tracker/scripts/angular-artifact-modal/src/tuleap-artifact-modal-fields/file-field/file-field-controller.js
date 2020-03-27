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

import { remove } from "lodash";

export default FileFieldController;

FileFieldController.$inject = [];

function FileFieldController() {
    const self = this;
    Object.assign(self, {
        addTemporaryFileInput,
        resetTemporaryFileInput,
        toggleMarkedForRemoval,
    });

    function addTemporaryFileInput() {
        self.value_model.temporary_files.push({});
    }

    function resetTemporaryFileInput(index) {
        if (self.value_model.temporary_files[index] === undefined) {
            return;
        }

        self.value_model.temporary_files[index] = {
            file: {},
            description: "",
        };
    }

    function toggleMarkedForRemoval(file, index) {
        if (file.marked_for_removal) {
            return unmarkFileForRemoval(file, index);
        }

        return markFileForRemoval(file);
    }

    function markFileForRemoval(file_to_mark) {
        remove(self.value_model.value, function (id) {
            return id === file_to_mark.id;
        });

        file_to_mark.marked_for_removal = true;
    }

    function unmarkFileForRemoval(file_to_unmark, index) {
        self.value_model.value.splice(index, 0, file_to_unmark.id);

        file_to_unmark.marked_for_removal = false;
    }
}
