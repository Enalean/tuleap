var submilestoneController = function($scope, submilestoneService, Artifact) {
    $scope.name = 'submilestones';
    $scope.showItems = false;
    $scope.init = function (milestone_id) {
        $scope.showItems = ! $scope.showItems;
        if ($scope.milestone_id) {
            return;
        }

        $scope.milestone_id = milestone_id;
        submilestoneService.content({milestoneId: milestone_id}, function (data) {
            $scope.planned = data;
        });
    };

    $scope.update = function (index) {
        var id, params;

        if (index) {
            id = $scope.planned[index].id;
            params = {
                compared_to: $scope.planned[index - 1].id,
                direction: 'after'
            };
        } else {
            id = $scope.planned[0].id;
            params = {
                compared_to: $scope.planned[1].id,
                direction: 'before'
            };
        }
        Artifact.reorder({id: id}, params);
    };

//    $scope.change = function(submilestone) {
//        submilestone.$save({milestoneId: submilestone.id});
//    }
};
