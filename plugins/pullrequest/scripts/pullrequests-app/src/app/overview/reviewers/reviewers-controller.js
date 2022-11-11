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

export default ReviewersController;

ReviewersController.$inject = [
    "SharedPropertiesService",
    "ReviewersService",
    "PullRequestService",
    "UpdateReviewersModalService",
];

function ReviewersController(
    SharedPropertiesService,
    ReviewersService,
    PullRequestService,
    UpdateReviewersModalService
) {
    const self = this;

    Object.assign(self, {
        pull_request: {},
        reviewers: [],
        loading_reviewers: true,
        showUpdateReviewersModal: showUpdateReviewersModal,
        hasEditRight: hasEditRight,
        $onInit: init,
    });

    function init() {
        SharedPropertiesService.whenReady().then(function () {
            self.pull_request = SharedPropertiesService.getPullRequest();

            ReviewersService.getReviewers(self.pull_request)
                .then((reviewers) => {
                    self.reviewers = reviewers;
                })
                .finally(() => {
                    self.loading_reviewers = false;
                });
        });
    }

    function showUpdateReviewersModal() {
        UpdateReviewersModalService.showModal(self.pull_request, self.reviewers);
    }

    function hasEditRight() {
        return (
            self.pull_request.user_can_merge &&
            !PullRequestService.isPullRequestClosed(self.pull_request)
        );
    }
}
