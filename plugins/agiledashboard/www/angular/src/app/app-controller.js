var controllers = angular.module('planningControllers', []);

controllers.controller('MilestoneCtrl', ['$scope', 'Milestone', function ($scope, Milestone) {
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
        var ids;
        if (index) {
            ids = [$scope.toBePlanneds[index - 1].id, $scope.toBePlanneds[index].id];
            Milestone.update_backlog({
                milestoneId: $scope.milestone_id,
                moved_artifact: $scope.toBePlanneds[index].id,
                compared_to: $scope.toBePlanneds[index - 1].id,
                direction: 'after'
            }, ids);
        } else {
            ids = [$scope.toBePlanneds[0].id, $scope.toBePlanneds[1].id];
            Milestone.update_backlog({
                milestoneId: $scope.milestone_id,
                moved_artifact: $scope.toBePlanneds[0].id,
                compared_to: $scope.toBePlanneds[1].id,
                direction: 'before'
            }, ids);
        }
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
