/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import "./update-reviewers-modal.tpl.html";
import controller from "./update-reviewers-modal-controller.js";

export default UpdateReviewersModalService;

UpdateReviewersModalService.$inject = ["TlpModalService"];

function UpdateReviewersModalService(TlpModalService) {
    const self = this;

    Object.assign(self, {
        showModal,
    });

    function showModal(pull_request, reviewers) {
        TlpModalService.open({
            templateUrl: "update-reviewers-modal.tpl.html",
            controller,
            controllerAs: "update_reviewers_modal",
            tlpModalOptions: {
                keyboard: true,
                backdrop: "static",
            },
            resolve: { pull_request, reviewers },
        });
    }
}
