angular
    .module('campaign')
    .controller('CampaignEditCtrl', CampaignEditCtrl)
    .constant('CampaignEditConstants', {
        'SELECTION_STATES': {
            unselected: 'unselected',
            selected  : 'selected',
            added     : 'added',
            removed   : 'removed',
            all       : 'all',
            some      : 'some',
            none      : 'none',
        },
    });

CampaignEditCtrl.$inject = [
    '$scope',
    '$q',
    '$modalInstance',
    '$state',
    '$filter',
    '$modal',
    'SharedPropertiesService',
    'CampaignService',
    'DefinitionService',
    'ExecutionService',
    'ExecutionRestService',
    'NewTuleapArtifactModalService',
    'CampaignEditConstants',
    'editCampaignCallback'
];

function CampaignEditCtrl(
    $scope,
    $q,
    $modalInstance,
    $state,
    $filter,
    $modal,
    SharedPropertiesService,
    CampaignService,
    DefinitionService,
    ExecutionService,
    ExecutionRestService,
    NewTuleapArtifactModalService,
    CampaignEditConstants,
    editCampaignCallback
) {

    _.extend($scope, {
        tests_list            : {},
        showAddTestModal      : showAddTestModal,
        matchProperties       : matchProperties,
        toggleCategory        : toggleCategory,
        toggleTest            : toggleTest,
        addedTests            : addedTests,
        removedTests          : removedTests,
        cancel                : cancel,
        editCampaign          : editCampaign,
        categoryCheckmark     : categoryCheckmark,
        testCheckmark         : testCheckmark,
        diffState             : diffState,
    });

    init();

    function init() {
        var project_id  = SharedPropertiesService.getProjectId();
        var campaign_id = $state.params.id;

        SharedPropertiesService.setCampaignId(campaign_id);

        $scope.campaign = CampaignService.getCampaign(campaign_id);
        $scope.search   = '';

        $q.all([
            loadDefinitions(project_id),
            loadExecutions(campaign_id)
        ]).then(function(results) {
            var definitions = results[0],
                executions = results[1];
            $scope.tests_list = getInitialTestsList(definitions, executions);
        });
    }

    function loadDefinitions(project_id, limit, offset, definitions) {
        limit       = limit || 10;
        offset      = offset || 0;
        definitions = definitions || [];

        return DefinitionService.getDefinitions(project_id, limit, offset).then(function(data) {
            definitions = definitions.concat(data.results);

            if (definitions.length === data.total) {
                return definitions;
            }

            return loadDefinitions(project_id, limit, offset + limit, definitions);
        });
    }

    function loadExecutions(campaign_id) {
        return ExecutionService.loadExecutions(campaign_id).then(function() {
            return _.reduce(
                _.map(ExecutionService.executions_by_categories_by_campaigns[campaign_id], function(category) {
                    return category.executions;
                }),
                function(result, execution) {
                    return result.concat(execution);
                },
                []
            );
        });
    }

    function getInitialTestsList(definitions, executions) {
        var tests_list = {};

        _.forEach(definitions, function(definition) {
            var category = definition.category;

            if (! _.has(tests_list, category)) {
                tests_list[category] = buildCategory(category);
            }

            tests_list[category].tests[definition.id] = buildTest(definition, null, false);
        });

        _.forEach(executions, function(execution) {
            var definition = execution.definition;
            var category = definition.category || DefinitionService.UNCATEGORIZED;

            _.merge(tests_list[category].tests[definition.id], {
                execution: execution,
                selected: true
            });
        });

        return tests_list;
    }

    function buildCategory(category) {
        return {
            tests: {},
            label: category
        };
    }

    function buildTest(definition, execution, selected) {
        return {
            definition: definition,
            execution: execution,
            selected: selected
        };
    }

    function matchProperties(search) {
        return function(test) {
            return test.definition.id.toString().indexOf(search) === 0 ||
                   test.definition.summary.indexOf(search) !== -1 ||
                   test.definition.category.indexOf(search) !== -1;
        };
    }

    function selectedTests(category) {
        return _.filter(category.tests, function(test) {
            return test.selected;
        });
    }

    function toggleCategory(category) {
        if (selectedTests(category).length === _.size(category.tests)) {
            _.forEach(category.tests, function(test) { test.selected = false; });
        } else {
            _.forEach(category.tests, function(test) { test.selected = true; });
        }
    }

    function toggleTest(test) {
        test.selected = !test.selected;
    }

    function categoryCheckmark(category) {
        switch (selectedTests(category).length) {
          case 0:
              return 'icon-check-empty';
          case _.size(category.tests):
              return 'icon-check';
          default:
              return 'icon-check-minus';
        }
    }

    function testCheckmark(test) {
        return test.selected ? 'icon-check' : 'icon-check-empty';
    }

    function diffState(test) {
        if (test.execution !== null && test.selected) {
            return 'test-selected';
        } else if (test.execution !== null) {
            return 'test-removed';
        } else if (test.selected) {
            return 'test-added';
        } else {
            return 'test-unselected';
        }
    }

    function addedTests() {
        return _.flatten(_.map($scope.tests_list, function(category) {
            return _.filter(category.tests, function(test) {
                return test.execution === null && test.selected;
            });
        }));
    }

    function removedTests() {
        return _.flatten(_.map($scope.tests_list, function(category) {
            return _.filter(category.tests, function(test) {
                return test.execution !== null && !test.selected;
            });
        }));
    }

    function showAddTestModal() {
        var callback = function(definition_id) {
            DefinitionService.getDefinitionById(definition_id).then(addTest);
        };

        NewTuleapArtifactModalService.showCreation(SharedPropertiesService.getDefinitionTrackerId(), null, callback);
    }

    function addTest(definition) {
        var category = definition.category || DefinitionService.UNCATEGORIZED;

        if (! _.has($scope.tests_list, category)) {
            $scope.tests_list[category] = buildCategory(category);
        }

        $scope.tests_list[category].tests[definition.id] = buildTest(definition, null, true);
    }

    function cancel() {
        $modalInstance.dismiss();
    }

    function editCampaign(campaign) {
        $scope.submitting_changes = true;

        var definition_ids = addedTests().map(function(test) { return test.definition.id; });
        var execution_ids = removedTests().map(function(test) { return test.execution.id; });

        var campaign_update   = CampaignService
            .patchCampaign(campaign.id, campaign.label);
        var executions_update = CampaignService
            .patchExecutions(campaign.id, definition_ids, execution_ids);

        $q.all([campaign_update, executions_update]).then(function(responses) {
            $scope.submitting_changes = false;

            if (editCampaignCallback) {
                var campaign = responses[0];
                editCampaignCallback(campaign);
            }

            $modalInstance.close();
        });
    }
}

