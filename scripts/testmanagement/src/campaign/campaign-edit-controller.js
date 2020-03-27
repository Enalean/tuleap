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

import _ from "lodash";

import { UNCATEGORIZED } from "../definition/definition-constants.js";
import {
    buildInitialTestsList,
    buildCategory,
    buildTest,
} from "./edit-campaign/edit-campaign-model-builder.js";

export default CampaignEditCtrl;

CampaignEditCtrl.$inject = [
    "modal_instance",
    "$scope",
    "$q",
    "$state",
    "SharedPropertiesService",
    "CampaignService",
    "DefinitionService",
    "ExecutionService",
    "NewTuleapArtifactModalService",
    "editCampaignCallback",
];

function CampaignEditCtrl(
    modal_instance,
    $scope,
    $q,
    $state,
    SharedPropertiesService,
    CampaignService,
    DefinitionService,
    ExecutionService,
    NewTuleapArtifactModalService,
    editCampaignCallback
) {
    let project_id, campaign_id;
    const self = this;
    Object.assign(self, {
        $onInit: init,
        loadDefinitions,
    });

    Object.assign($scope, {
        tests_list: {},
        test_reports: [],
        filters: {},
        selectReportTests,
        showAddTestModal,
        toggleCategory,
        toggleTest,
        addedTests,
        removedTests,
        editCampaign,
        categoryCheckmark,
        testCheckmark,
        diffState,
    });

    function init() {
        project_id = SharedPropertiesService.getProjectId();
        campaign_id = $state.params.id;

        SharedPropertiesService.setCampaignId(campaign_id);

        CampaignService.getCampaign(campaign_id).then((campaign) => {
            $scope.campaign = campaign;
            $scope.filters.search = "";

            loadTestReports();

            $q.all([loadDefinitions(), loadExecutions()]).then(function (results) {
                var definitions = results[0],
                    executions = results[1];
                $scope.tests_list = buildInitialTestsList(definitions, executions);
            });
        });
    }

    function loadTestReports() {
        DefinitionService.getDefinitionReports().then(function (reports) {
            // data: [{id: <int>, label: <string>}]
            $scope.test_reports = reports;
        });
    }

    function loadDefinitions(report_id) {
        return DefinitionService.getDefinitions(project_id, report_id);
    }

    function loadExecutions() {
        return ExecutionService.loadExecutions(campaign_id).then(function () {
            return ExecutionService.executionsForCampaign(campaign_id);
        });
    }

    function selectedTests(category) {
        //eslint-disable-next-line you-dont-need-lodash-underscore/filter
        return _.filter(category.tests, function (test) {
            return test.selected;
        });
    }

    function toggleCategory(category) {
        //category.tests is not an array
        //eslint-disable-next-line you-dont-need-lodash-underscore/size
        if (selectedTests(category).length === _.size(category.tests)) {
            //eslint-disable-next-line you-dont-need-lodash-underscore/for-each
            _.forEach(category.tests, function (test) {
                test.selected = false;
            });
        } else {
            //eslint-disable-next-line you-dont-need-lodash-underscore/for-each
            _.forEach(category.tests, function (test) {
                test.selected = true;
            });
        }
    }

    function toggleTest(test) {
        test.selected = !test.selected;
    }

    function categoryCheckmark(category) {
        switch (selectedTests(category).length) {
            case 0:
                return "fa-square-o";
            //eslint-disable-next-line you-dont-need-lodash-underscore/size
            case _.size(category.tests):
                return "fa-check-square-o";
            default:
                return "fa-minus-square-o";
        }
    }

    function testCheckmark(test) {
        return test.selected ? "fa-check-square-o" : "fa-square-o";
    }

    function diffState(test) {
        if (test.execution !== null && test.selected) {
            return "selected";
        } else if (test.execution !== null) {
            return "removed";
        } else if (test.selected) {
            return "added";
        } else {
            return "unselected";
        }
    }

    function addedTests() {
        return _($scope.tests_list)
            .map(function (category) {
                //eslint-disable-next-line you-dont-need-lodash-underscore/select
                return _.select(category.tests, { execution: null, selected: true });
            })
            .flatten()
            .value();
    }

    function removedTests() {
        return _($scope.tests_list)
            .map(function (category) {
                return _(category.tests)
                    .reject({ execution: null })
                    .reject({ selected: true })
                    .value();
            })
            .flatten()
            .value();
    }

    function selectReportTests() {
        var selected_report = $scope.filters.selected_report;

        if (selected_report === "") {
            return $q.when();
        }

        return $q.when(self.loadDefinitions(selected_report)).then((definitions) => {
            Object.values($scope.tests_list).forEach((category) => {
                Object.values(category.tests).forEach((test) => {
                    test.selected = definitions.some(
                        (definition) => definition.id === test.definition.id
                    );
                });
            });
        });
    }

    function showAddTestModal() {
        var callback = function (definition_id) {
            DefinitionService.getDefinitionById(definition_id).then(addTest);
        };

        NewTuleapArtifactModalService.showCreation(
            SharedPropertiesService.getDefinitionTrackerId(),
            null,
            callback
        );
    }

    function addTest(definition) {
        var category = definition.category || UNCATEGORIZED;

        if (!_.has($scope.tests_list, category)) {
            $scope.tests_list[category] = buildCategory(category);
        }

        $scope.tests_list[category].tests[definition.id] = buildTest(definition, null, true);
    }

    function editCampaign(campaign) {
        $scope.submitting_changes = true;

        const definition_ids = addedTests().map((test) => {
            return test.definition.id;
        });
        const execution_ids = removedTests().map((test) => {
            return test.execution.id;
        });

        CampaignService.patchExecutions(campaign.id, definition_ids, execution_ids)
            .then(() => {
                return CampaignService.patchCampaign(
                    campaign.id,
                    campaign.label,
                    campaign.job_configuration
                );
            })
            .then((response) => {
                $scope.submitting_changes = false;

                if (editCampaignCallback) {
                    editCampaignCallback(response);
                }

                modal_instance.tlp_modal.hide();
            });
    }
}
