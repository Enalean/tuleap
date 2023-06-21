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

export default ReviewersRestService;

ReviewersRestService.$inject = ["$http", "$q", "ErrorModalService"];

function ReviewersRestService($http, $q, ErrorModalService) {
    const self = this;

    Object.assign(self, {
        getReviewers,
        updateReviewers,
        searchUsers,
    });

    function getReviewers(pull_request_id) {
        return $http
            .get("/api/v1/pull_requests/" + encodeURIComponent(pull_request_id) + "/reviewers")
            .catch((response) => {
                ErrorModalService.showErrorResponseMessage(response);
            });
    }

    function updateReviewers(pull_request_id, reviewer_representations) {
        return $http
            .put("/api/v1/pull_requests/" + encodeURIComponent(pull_request_id) + "/reviewers", {
                users: reviewer_representations,
            })
            .catch((response) => {
                ErrorModalService.showErrorResponseMessage(response);
            });
    }

    function searchUsers(pull_request_id, query) {
        return $http
            .get(
                "/plugins/pullrequest/autocompleter_reviewers/" +
                    encodeURIComponent(pull_request_id),
                { params: { name: query } }
            )
            .then((response) => {
                return response;
            })
            .catch((response) => {
                ErrorModalService.showErrorResponseMessage(response);
            });
    }
}
