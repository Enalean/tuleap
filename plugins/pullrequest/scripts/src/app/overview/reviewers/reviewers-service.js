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

export default ReviewersService;

ReviewersService.$inject = ["ReviewersRestService"];

function ReviewersService(ReviewersRestService) {
    const self = this;

    Object.assign(self, {
        getReviewers,
        updateReviewers,
        buildUserRepresentationsForPut,
    });

    function getReviewers(pull_request) {
        let reviewers = [];

        return ReviewersRestService.getReviewers(pull_request.id).then((response) => {
            reviewers = response.data.users;

            return reviewers;
        });
    }

    function updateReviewers(pull_request, reviewer_representations) {
        return ReviewersRestService.updateReviewers(pull_request.id, reviewer_representations);
    }

    function buildUserRepresentationsForPut(user_representations) {
        return Array.from(user_representations, (user) => {
            return { id: parseInt(user.id, 10) };
        });
    }
}
