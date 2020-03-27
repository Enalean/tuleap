/*
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

import _ from "lodash";
import angular from "angular";

import { sortAlphabetically } from "../ksort.js";
import { setError } from "../feedback-state.js";

export default ExecutionListCtrl;

ExecutionListCtrl.$inject = [
    "$scope",
    "$state",
    "$filter",
    "gettextCatalog",
    "ExecutionService",
    "CampaignService",
    "SocketService",
    "SharedPropertiesService",
    "ExecutionRestService",
];

function ExecutionListCtrl(
    $scope,
    $state,
    $filter,
    gettextCatalog,
    ExecutionService,
    CampaignService,
    SocketService,
    SharedPropertiesService,
    ExecutionRestService
) {
    const self = this;
    Object.assign(self, {
        $onInit: initialization,
        loadExecutions,
    });

    Object.assign($scope, {
        checkActiveClassOnExecution,
        toggleStatus,
        viewTestExecution,
        canCategoryBeDisplayed,
        hideDetailsForRemovedTestExecution,
        shouldShowEmptyState: () => self.should_show_empty_state,
    });

    function checkActiveClassOnExecution(execution) {
        return $state.includes("campaigns.executions.detail", {
            execid: execution.id,
            defid: execution.definition.id,
        });
    }

    function toggleStatus(executionStatus) {
        $scope.status[executionStatus] = !$scope.status[executionStatus];
    }

    function viewTestExecution(current_execution) {
        var old_execution,
            old_execution_id = "";

        if (_.has(ExecutionService.executions, $scope.execution_id)) {
            old_execution = ExecutionService.executions[$scope.execution_id];
        }

        if (!_.isEmpty(old_execution)) {
            if (current_execution.id !== old_execution.id) {
                old_execution_id = old_execution.id;
                updateViewTestExecution(current_execution.id, old_execution_id);
            }
        } else {
            updateViewTestExecution(current_execution.id, old_execution_id);
        }
    }

    $scope.$on("$destroy", function () {
        var toolbar = angular.element(".toolbar");
        if (toolbar) {
            toolbar.removeClass("hide-toolbar");
        }

        if ($scope.execution_id) {
            ExecutionRestService.leaveTestExecution($scope.execution_id);
            ExecutionService.removeViewTestExecution(
                $scope.execution_id,
                SharedPropertiesService.getCurrentUser()
            );
        }

        ExecutionService.removeAllPresencesOnCampaign();
    });

    $scope.$on("execution-detail-destroy", function () {
        $scope.execution_id = "";
    });

    $scope.$on("controller-reload", function () {
        initialization();
    });

    SocketService.listenNodeJSServer().then(
        function () {
            SocketService.listenToUserScore();
            SocketService.listenTokenExpired();
            SocketService.listenToExecutionViewed();
            SocketService.listenToExecutionCreated();
            SocketService.listenToExecutionUpdated();
            SocketService.listenToExecutionDeleted(function () {
                hideDetailsForRemovedTestExecution();
            });
            SocketService.listenToExecutionLeft();
            SocketService.listenToCampaignUpdated(function (campaign) {
                $scope.campaign = campaign;
                ExecutionService.updateCampaign(campaign);
            });
        },
        () => {
            // ignore the fact that there is no nodejs server
        }
    );

    function initialization() {
        var toolbar = angular.element(".toolbar");
        if (toolbar) {
            toolbar.addClass("hide-toolbar");
        }

        $scope.campaign_id = $state.params.id;
        $scope.execution_id = $state.params.execid;

        SharedPropertiesService.setCampaignId($scope.campaign_id);

        self.loadExecutions();
        CampaignService.getCampaign($scope.campaign_id).then((campaign) => {
            $scope.campaign = campaign;
            $scope.search = "";
            $scope.loading = loading;
            $scope.status = {
                passed: false,
                failed: false,
                blocked: false,
                notrun: false,
            };

            ExecutionService.updateCampaign($scope.campaign);
        });
        watchAndSortCategories();
    }

    function watchAndSortCategories() {
        $scope.$watch(
            () => ExecutionService.executions_by_categories_by_campaigns[$scope.campaign_id],
            (categories) => {
                $scope.categories = sortAlphabetically(categories);
            },
            true
        );
    }

    function loadExecutions() {
        return ExecutionService.loadExecutions($scope.campaign_id).then(
            (executions) => {
                if (executions.length === 0) {
                    self.should_show_empty_state = true;
                }

                ExecutionService.removeAllViewTestExecution();
                if ($scope.execution_id) {
                    updateViewTestExecution($scope.execution_id, "");
                }

                ExecutionService.executions_loaded = true;
                ExecutionService.displayPresencesForAllExecutions();
            },
            (error) =>
                setError(
                    gettextCatalog.getString(
                        "An error occurred while loading the tests. {{ error }}",
                        { error: error.data.error.message }
                    )
                )
        );
    }

    function updateViewTestExecution(current_execution_id, old_execution_id) {
        ExecutionService.addPresenceCampaign(SharedPropertiesService.getCurrentUser());

        ExecutionRestService.changePresenceOnTestExecution(
            current_execution_id,
            old_execution_id
        ).then(function () {
            ExecutionService.removeViewTestExecution(
                old_execution_id,
                SharedPropertiesService.getCurrentUser()
            );
            ExecutionService.viewTestExecution(
                current_execution_id,
                SharedPropertiesService.getCurrentUser()
            );
            $scope.execution_id = current_execution_id;
        });
    }

    function hideDetailsForRemovedTestExecution() {
        if ($state.includes("campaigns.executions.detail")) {
            var campaign_executions = ExecutionService.executionsForCampaign($scope.campaign_id),
                //eslint-disable-next-line you-dont-need-lodash-underscore/any
                current_execution_exists = _.any(campaign_executions, checkActiveClassOnExecution);

            if (!current_execution_exists) {
                $state.go("^");
            }
        }
    }

    function loading() {
        return ExecutionService.loading[$scope.campaign_id] === true;
    }

    function canCategoryBeDisplayed(category) {
        var filtered_executions = $filter("ExecutionListFilter")(
            category.executions,
            $scope.search,
            $scope.status
        );

        //eslint-disable-next-line you-dont-need-lodash-underscore/size
        return _.size(filtered_executions) > 0;
    }
}
