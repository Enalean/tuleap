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

// eslint-disable-next-line you-dont-need-lodash-underscore/some
import { has, isEmpty, some } from "lodash-es";
import angular from "angular";

import { sortAlphabetically } from "../ksort.js";
import { setError } from "../feedback-state.js";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { KeyboardShortcuts } from "../keyboard-navigation/setup-shortcuts";

export default ExecutionListCtrl;

ExecutionListCtrl.$inject = [
    "$scope",
    "$state",
    "$filter",
    "$element",
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
    $element,
    gettextCatalog,
    ExecutionService,
    CampaignService,
    SocketService,
    SharedPropertiesService,
    ExecutionRestService,
) {
    const self = this;
    Object.assign(self, {
        $onInit: initialization,
        loadExecutions,
    });

    Object.assign($scope, {
        checkActiveClassOnExecution,
        viewTestExecution,
        canCategoryBeDisplayed,
        hideDetailsForRemovedTestExecution,
        shouldShowEmptyState,
        isStatusHidden,
        scrollToCurrentTest,
        isScrollToTestButtonDisabled,
    });

    function checkActiveClassOnExecution(execution) {
        return $state.includes("campaigns.executions.detail", {
            execid: execution.id,
            defid: execution.definition.id,
        });
    }

    function viewTestExecution(current_execution) {
        var old_execution,
            old_execution_id = "";

        if (has(ExecutionService.executions, $scope.execution_id)) {
            old_execution = ExecutionService.executions[$scope.execution_id];
        }

        if (!isEmpty(old_execution)) {
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
                SharedPropertiesService.getCurrentUser(),
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
        },
    );

    function initialization() {
        var toolbar = angular.element(".toolbar");
        if (toolbar) {
            toolbar.addClass("hide-toolbar");
        }

        $scope.campaign_id = $state.params.id;
        $scope.execution_id = $state.params.execid;
        $scope.search = "";
        $scope.loading = loading;
        $scope.status = {
            passed: true,
            failed: true,
            blocked: true,
            notrun: true,
        };
        $scope.are_automated_tests_shown = false;
        $scope.is_scrolling_to_current_test = false;

        SharedPropertiesService.setCampaignId($scope.campaign_id);

        self.loadExecutions();
        CampaignService.getCampaign($scope.campaign_id).then((campaign) => {
            $scope.campaign = campaign;
            ExecutionService.updateCampaign($scope.campaign);
        });

        const trigger = $element.find("#toggle-filters-dropdown");
        const dropdown_menu = $element.find("#filters-dropdown-menu");

        createDropdown(trigger[0], {
            dropdown_menu: dropdown_menu[0],
        });

        watchAndSortCategories();

        const keyboard_shortcuts = new KeyboardShortcuts(gettextCatalog);
        keyboard_shortcuts.setCampaignPageShortcuts();
    }

    function watchAndSortCategories() {
        $scope.$watch(
            () => ExecutionService.executions_by_categories_by_campaigns[$scope.campaign_id],
            (categories) => {
                $scope.categories = sortAlphabetically(categories);
            },
            true,
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
                    updateViewTestExecution($scope.execution_id, "").then(scrollToCurrentTest);
                }

                ExecutionService.executions_loaded = true;
                ExecutionService.displayPresencesForAllExecutions();
            },
            () => setError(gettextCatalog.getString("An error occurred while loading the tests.")),
        );
    }

    function updateViewTestExecution(current_execution_id, old_execution_id) {
        ExecutionService.addPresenceCampaign(SharedPropertiesService.getCurrentUser());

        return ExecutionRestService.changePresenceOnTestExecution(
            current_execution_id,
            old_execution_id,
        ).then(function () {
            ExecutionService.removeViewTestExecution(
                old_execution_id,
                SharedPropertiesService.getCurrentUser(),
            );
            ExecutionService.viewTestExecution(
                current_execution_id,
                SharedPropertiesService.getCurrentUser(),
            );
            $scope.execution_id = current_execution_id;
        });
    }

    function hideDetailsForRemovedTestExecution() {
        if ($state.includes("campaigns.executions.detail")) {
            var campaign_executions = ExecutionService.executionsForCampaign($scope.campaign_id),
                current_execution_exists = some(campaign_executions, checkActiveClassOnExecution);

            if (!current_execution_exists) {
                $state.go("^");
            }
        }
    }

    function loading() {
        return ExecutionService.loading[$scope.campaign_id] === true;
    }

    function canCategoryBeDisplayed(category) {
        const filtered_executions = $filter("ExecutionListFilter")(
            category.executions,
            $scope.search,
            $scope.status,
        );

        const filtered_auto_tests = $filter("AutomatedTestsFilter")(
            filtered_executions,
            $scope.are_automated_tests_shown,
        );

        return filtered_auto_tests.length > 0;
    }

    function isStatusHidden(status) {
        return $scope.status[status] === false;
    }

    function shouldShowEmptyState() {
        const are_all_tests_filters_disabled =
            $scope.status.passed === false &&
            $scope.status.blocked === false &&
            $scope.status.failed === false &&
            $scope.status.notrun === false;

        if (are_all_tests_filters_disabled) {
            return true;
        }

        return self.should_show_empty_state;
    }

    function scrollToCurrentTest() {
        if ($scope.is_scrolling_to_current_test === true) {
            return;
        }

        const list_item = angular.element(`[data-exec-id=${$scope.execution_id}]`)[0];

        if (!list_item) {
            return;
        }

        $scope.is_scrolling_to_current_test = true;

        list_item.scrollIntoView();
        list_item.classList.toggle("current-test-highlight");

        setTimeout(() => {
            list_item.classList.toggle("current-test-highlight");
            $scope.is_scrolling_to_current_test = false;
        }, 1000);
    }

    function isScrollToTestButtonDisabled() {
        return !$scope.execution_id;
    }
}
