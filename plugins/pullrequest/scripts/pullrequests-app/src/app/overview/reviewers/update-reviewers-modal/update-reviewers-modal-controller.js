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

import "./users-result-template.tpl.html";
import { select2 } from "tlp";
import angular, { isDefined } from "angular";

export default UpdateReviewersModalController;

UpdateReviewersModalController.$inject = [
    "$compile",
    "$rootScope",
    "$scope",
    "$templateCache",
    "modal_instance",
    "ReviewersService",
    "ReviewersRestService",
    "gettextCatalog",
    "pull_request",
    "reviewers",
];

function UpdateReviewersModalController(
    $compile,
    $rootScope,
    $scope,
    $templateCache,
    modal_instance,
    ReviewersService,
    ReviewersRestService,
    gettextCatalog,
    pull_request,
    reviewers
) {
    const self = this;

    Object.assign(self, {
        save,
        is_saving: false,
        reviewers_selection: reviewers.slice(0),
        is_there_at_least_one_reviewers_already_set: reviewers.length > 0,
        $onInit: init,
        templateUserResult,
        templateUserSelection,
        handleUsersValueSelection,
        handleUsersValueUnselection,
        handleUsersValueClearing,
        isClearModal,
        isAddModal,
        isEditModalAtStart,
        isEditModal,
    });

    function init() {
        const user_autocompleter = modal_instance.tlp_modal.element.querySelector(
            "#update-reviewers-modal-select"
        );

        select2(user_autocompleter, {
            placeholder: gettextCatalog.getString("Select reviewers"),
            allowClear: true,
            minimumInputLength: 3,
            ajax: {
                transport: function (params, success, failure) {
                    // Pagination has been intentionally not managed at all
                    return ReviewersRestService.searchUsers(pull_request.id, params.data.term).then(
                        function (response) {
                            success({
                                results: response.data,
                            });
                        },
                        function (error) {
                            failure(error);
                        }
                    );
                },
            },
            templateResult: self.templateUserResult,
            templateSelection: self.templateUserSelection,
            width: "100%",
        });

        angular.element(user_autocompleter).on("select2:selecting", self.handleUsersValueSelection);
        angular
            .element(user_autocompleter)
            .on("select2:unselecting", self.handleUsersValueUnselection);

        angular.element(user_autocompleter).on("select2:clear", self.handleUsersValueClearing);
    }

    function handleUsersValueSelection(event) {
        const new_selection = event.params.args.data;

        self.reviewers_selection.push(new_selection);
    }

    function handleUsersValueUnselection(event) {
        const removed_selection = event.params.args.data;

        self.reviewers_selection = self.reviewers_selection.filter(
            (user) => parseInt(user.id, 10) !== parseInt(removed_selection.id, 10)
        );

        // Without this call, sometimes the template isn't updated
        $scope.$digest();
    }

    function handleUsersValueClearing() {
        self.reviewers_selection.length = 0;

        // Without this call, sometimes the template isn't updated
        $scope.$digest();
    }

    function templateUserResult(result) {
        if (result.loading === true) {
            return result.text;
        }

        return templateUser(result);
    }

    function templateUserSelection(result) {
        let user_representation = getUserRepresentationForInitialSelection(result);
        user_representation = isDefined(user_representation) ? user_representation : result;

        return templateUser(user_representation);
    }

    function templateUser(result) {
        const user_display_template = $templateCache.get("users-result-template.tpl.html");
        const isolate_scope = $rootScope.$new();

        isolate_scope.user = result;

        return $compile(user_display_template)(isolate_scope);
    }

    function getUserRepresentationForInitialSelection(result) {
        return reviewers.find((user) => {
            return result.id === user.id.toString();
        });
    }

    function isClearModal() {
        return (
            self.is_there_at_least_one_reviewers_already_set &&
            self.reviewers_selection.length === 0
        );
    }

    function isAddModal() {
        return !self.is_there_at_least_one_reviewers_already_set;
    }

    function isEditModalAtStart() {
        return self.is_there_at_least_one_reviewers_already_set;
    }

    function isEditModal() {
        return (
            self.is_there_at_least_one_reviewers_already_set && self.reviewers_selection.length > 0
        );
    }

    function save() {
        self.is_saving = true;

        const user_representations = ReviewersService.buildUserRepresentationsForPut(
            self.reviewers_selection
        );

        ReviewersService.updateReviewers(pull_request, user_representations)
            .then(() => {
                reviewers.length = 0;
                Array.prototype.push.apply(reviewers, self.reviewers_selection);

                modal_instance.tlp_modal.hide();
            })
            .finally(() => {
                self.is_saving = false;
            });
    }
}
