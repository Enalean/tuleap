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

import { createModal } from "tlp";
import "./execution-attachments-creation-error-modal.tpl.html";

export default {
    bindings: {
        errors: "<",
    },
    templateUrl: "execution-attachments-creation-error-modal.tpl.html",
    controller,
};

controller.$inject = ["$element", "$scope"];

function controller($element, $scope) {
    const self = this;

    Object.assign(self, {
        $onInit,
        $onDestroy,
        modal: null,
    });

    function $onInit() {
        $scope.$watch(
            () => self.errors.length > 0,
            (has_errors) => {
                if (!has_errors) {
                    return;
                }

                if (self.modal === null) {
                    createErrorModal();
                }

                if (self.modal.is_shown) {
                    return;
                }

                self.modal.show();
            },
        );
    }

    function $onDestroy() {
        if (self.modal === null) {
            return;
        }
        self.modal.destroy();
    }

    function createErrorModal() {
        self.modal = createModal($element[0].querySelector("#execution-attachment-creation-modal"));

        self.modal.addEventListener("tlp-modal-hidden", close);
    }

    function close() {
        $scope.$emit("user-has-closed-the-file-creation-errors-modal");
    }
}
