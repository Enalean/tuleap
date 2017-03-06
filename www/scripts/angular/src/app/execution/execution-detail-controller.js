angular
    .module('execution')
    .controller('ExecutionDetailCtrl', ExecutionDetailCtrl);

ExecutionDetailCtrl.$inject = [
    '$scope',
    '$state',
    '$sce',
    '$rootScope',
    'ExecutionService',
    'DefinitionService',
    'SharedPropertiesService',
    'ArtifactLinksGraphService',
    'ArtifactLinksGraphModalLoading',
    'NewTuleapArtifactModalService',
    'ExecutionRestService'
];

function ExecutionDetailCtrl(
    $scope,
    $state,
    $sce,
    $rootScope,
    ExecutionService,
    DefinitionService,
    SharedPropertiesService,
    ArtifactLinksGraphService,
    ArtifactLinksGraphModalLoading,
    NewTuleapArtifactModalService,
    ExecutionRestService
) {
    var execution_id,
        campaign_id;

    $scope.pass                        = pass;
    $scope.fail                        = fail;
    $scope.block                       = block;
    $scope.notrun                      = notrun;
    $scope.sanitizeHtml                = sanitizeHtml;
    $scope.getStatusLabel              = getStatusLabel;
    $scope.showArtifactLinksGraphModal = showArtifactLinksGraphModal;
    $scope.showEditArtifactModal       = showEditArtifactModal;

    initialization();
    resetTimer();

    $scope.$on('controller-reload', function() {
        initialization();
    });

    $scope.$on('$destroy', function() {
        var future_execution_id = parseInt($state.params.execid, 10);
        if (! _.isFinite(future_execution_id)) {
            $rootScope.$broadcast('execution-detail-destroy');
            ExecutionRestService.leaveTestExecution(execution_id);
            ExecutionService.removeViewTestExecution(execution_id, SharedPropertiesService.getCurrentUser());
        }
    });

    function initialization() {
        execution_id = parseInt($state.params.execid, 10);
        campaign_id  = parseInt($state.params.id, 10);

        ExecutionService.loadExecutions(campaign_id);

        if (isCurrentExecutionLoaded()) {
            retrieveCurrentExecution();
        } else {
            waitForExecutionToBeLoaded();
        }

        $scope.artifact_links_graph_modal_loading = ArtifactLinksGraphModalLoading.loading;
        $scope.edit_artifact_modal_loading        = NewTuleapArtifactModalService.loading;
    }

    function resetTimer() {
        $scope.timer = {
            execution_time: 0
        };
    }

    function showArtifactLinksGraphModal(execution) {
        ArtifactLinksGraphService.showGraphModal(execution);
    }

    function showEditModal($event, backlog_item, milestone) {
        var when_left_mouse_click = 1;

        function callback(item_id) {
            return BacklogItemCollectionService.refreshBacklogItem(item_id).then(function() {
                if (milestone) {
                    MilestoneService.updateInitialEffort(milestone);
                }
            });
        }

        if ($event.which === when_left_mouse_click) {
            $event.preventDefault();

            NewTuleapArtifactModalService.showEdition(
                SharedPropertiesService.getUserId(),
                backlog_item.artifact.tracker.id,
                backlog_item.artifact.id,
                callback
            );
        }
    }

    function showEditArtifactModal($event, definition) {
        var when_left_mouse_click = 1;

        var old_category    = $scope.execution.definition.category;
        var current_user_id = SharedPropertiesService.getCurrentUser().id;

        function callback(artifact_id) {
            var executions = ExecutionService.getExecutionsByDefinitionId(artifact_id);

            return DefinitionService.getDefinitionById(artifact_id).then(function(definition) {
                _(executions).forEach(function(execution) {
                    $scope.execution = ExecutionService.executions[execution.id];

                    $scope.execution.definition.category = definition.category;
                    $scope.execution.definition.description = definition.description;
                    $scope.execution.definition.summary = definition.summary;

                    updateExecution(definition, old_category);
                });

                retrieveCurrentExecution();
            });
        }

        if ($event.which === when_left_mouse_click) {
            $event.preventDefault();

            DefinitionService.getArtifactById(definition.id).then(function(artifact) {
                NewTuleapArtifactModalService.showEdition(
                    current_user_id,
                    artifact.tracker.id,
                    artifact.id,
                    callback
                );
            });
        }
    }

    function waitForExecutionToBeLoaded() {
        var unbind = $rootScope.$on('bunchOfExecutionsLoaded', function () {
            if (isCurrentExecutionLoaded()) {
                retrieveCurrentExecution();
            }
        });
        $scope.$on('$destroy', unbind);
    }

    function retrieveCurrentExecution() {
        $scope.execution         = ExecutionService.executions[execution_id];
        $scope.execution.results = '';
        $scope.execution.saving  = false;
    }

    function isCurrentExecutionLoaded() {
        return typeof ExecutionService.executions[execution_id] !== 'undefined';
    }

    function sanitizeHtml(html) {
        if (html) {
            return $sce.trustAsHtml(html);
        }

        return null;
    }

    function pass(execution) {
        updateTime(execution);
        setNewStatus(execution, "passed");
    }

    function fail(execution) {
        updateTime(execution);
        setNewStatus(execution, "failed");
    }

    function block(execution) {
        updateTime(execution);
        setNewStatus(execution, "blocked");
    }

    function notrun(execution) {
        setNewStatus(execution, "notrun");
    }

    function updateTime(execution) {
        if (execution.time) {
            execution.time += $scope.timer.execution_time;
        }
    }

    function setNewStatus(execution, new_status) {
        execution.saving   = true;
        var execution_time = null;

        if (execution.time) {
            execution_time = execution.time;
        }
        ExecutionRestService.putTestExecution(execution.id, new_status, execution_time, execution.results).then(function(data) {
            ExecutionService.updateTestExecution(data);
            resetTimer();
        }).catch(function(response) {
            ExecutionService.displayError(execution, response);
        });
    }

    function getStatusLabel(status) {
        var labels = {
            passed: 'Passed',
            failed: 'Failed',
            blocked: 'Blocked',
            notrun: 'Not Run'
        };

        return labels[status];
    }

    function updateExecution(definition, old_category) {
        var category_updated = definition.category;

        if (category_updated === null) {
            category_updated = ExecutionService.UNCATEGORIZED;
        }

        if (old_category === null) {
            old_category = ExecutionService.UNCATEGORIZED;
        }

        var category_exist           = categoryExists(ExecutionService.categories, category_updated);
        var execution_already_placed = executionAlreadyPlaced($scope.execution, ExecutionService.categories, category_updated);

        if (! execution_already_placed) {
            removeCategory(ExecutionService.categories[old_category].executions, $scope.execution);
        }

        if (category_exist && ! execution_already_placed) {
            ExecutionService.categories[category_updated].executions.push($scope.execution);
        } else if (! category_exist && ! execution_already_placed) {
            ExecutionService.categories[category_updated] = {
                label: category_updated,
                executions: [$scope.execution]
            };
        }
    }

    function categoryExists(categories, category_updated) {
        return _.has(categories, category_updated);
    }

    function executionAlreadyPlaced(scopeExecution, categories, category_updated) {
        return _.has(categories, function(category) {
            return _.has(category.executions, scopeExecution.id, category_updated);
        });
    }

    function removeCategory(executions, scopeExecution) {
        _.remove(executions, function(execution) {
            return execution.id === scopeExecution.id;
        });
    }
}