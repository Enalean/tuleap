var controllers = angular.module('planningControllers', ['templates-app']);

controllers.controller('MilestoneCtrl', ['$scope', 'Milestone', 'Artifact', function ($scope, Milestone, Artifact) {
    var resource_params = {
        milestoneId: 0,
        offset: 0,
        limit: 100
    };

    function callback(data) {
        if (data.length === 0) {
            return;
        }

        if (data.length < resource_params.limit) {
            $scope.noMore = true;
        }

        $scope.toBePlanneds = $scope.toBePlanneds.concat(data);
        resource_params.offset += data.length;
        $scope.busy = false;
    }

    function loadFirstBunchOfBacklogItems(milestone_id) {
        $scope.busy = true;
        resource_params.milestoneId = milestone_id;
        Milestone.backlog(resource_params, callback);
    }

    function loadNextBunchOfBacklogItems() {
        if ($scope.busy) {
            return;
        }

        if ($scope.noMore) {
            return;
        }

        $scope.busy = true;
        Milestone.backlog(resource_params, callback);
    }

    $scope.init = function (milestone_id) {
        $scope.name         = 'backlog';
        $scope.milestone_id = milestone_id;
        $scope.toBePlanneds = [];
        $scope.noMore       = false;
        loadFirstBunchOfBacklogItems(milestone_id);
    };

    $scope.update = function (index) {
        var id, params;

        if (index) {
            id = $scope.toBePlanneds[index].id;
            params = {
                compared_to: $scope.toBePlanneds[index - 1].id,
                direction: 'after'
            };
        } else {
            id = $scope.toBePlanneds[0].id;
            params = {
                compared_to: $scope.toBePlanneds[1].id,
                direction: 'before'
            };
        }
        Artifact.reorder({id: id}, params);
    };

    $scope.loadMore = function () {
        loadNextBunchOfBacklogItems();
    };
}]);

controllers.controller('SubmilestonesCtrl', ['$scope', 'Milestone', function ($scope, Milestone) {
    $scope.init = function (milestone_id) {
        Milestone.milestones({milestoneId: milestone_id}, function (data) {
            $scope.submilestones = data;
        });
    };

    window.toto = function () {
        var milestone = $scope.submilestones[1];

        $scope.submilestones[1] = Milestone.get({milestoneId: milestone.id});
    };
}]);

controllers.controller('SortCtrl', ['$scope', function ($scope) {
    $scope.treeOptions = {
        dropped: function (event) {
            function findScope(direction) {
                var suitable_scope = event[direction].nodesScope;
                while (suitable_scope &&
                    suitable_scope.name !== 'backlogitem' &&
                    suitable_scope.name !== 'submilestones' &&
                    suitable_scope.name !== 'backlog'
                ) {
                    suitable_scope = suitable_scope.$parent;
                }

                return suitable_scope;
            }

            var source = findScope('source'),
                dest   = findScope('dest');

            if (source === dest) {
                // Only ranking has decent performance for now
                source.update(event.dest.index);
            } else if (source.name === 'backlog') {
                //dest.update();
            } else {
                //source.update();
                //dest.update();
            }
        }
    };
}]);

controllers.controller('BacklogItemCtrl', ['$scope', 'BacklogItem', 'Artifact', function ($scope, BacklogItem, Artifact) {
    $scope.name = 'backlogitem';

    $scope.template = 'planning/backlog-item/backlog-item.tpl.html';

    $scope.showChildren = false;

    $scope.children = false;

    $scope.backlogItemId = false;

    $scope.init = function (id) {
        $scope.backlogItemId = id;
    };

    $scope.toggleChildren = function ($event) {
        $scope.showChildren = ! $scope.showChildren;

        if ($scope.children === false) {

            $scope.children = BacklogItem.children({id: $scope.backlogItemId});
        }
    };

    $scope.editArtifact = function (artifactId) {
        tuleap.tracker.artifactModalInPlace.loadEditArtifactModal(artifactId);
    };

    $scope.addChild = function (artifactId, trackerId) {
        tuleap.tracker.artifactModalInPlace.loadCreateArtifactModal(trackerId, artifactId);
    };

    $scope.update = function (index) {
        var id, params;

        if (index) {
            id = $scope.children[index].id;
            params = {
                compared_to: $scope.children[index - 1].id,
                direction: 'after'
            };
        } else {
            id = $scope.children[0].id;
            params = {
                compared_to: $scope.children[1].id,
                direction: 'before'
            };
        }
        Artifact.reorder({id: id}, params);
    };
}]);
