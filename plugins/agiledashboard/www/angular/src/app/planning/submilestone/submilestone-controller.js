var submilestoneController = function($scope, submilestoneService) {
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
        var ids;

        if (index) {
            submilestoneService.update_content({
                milestoneId: $scope.milestone_id,
                moved_artifact: $scope.planned[index].id,
                compared_to: $scope.planned[index - 1].id,
                direction: 'after'
            }, ids);
            // ids = [$scope.planned[index - 1].id, $scope.planned[index].id];
            //
        } else {
            submilestoneService.update_content({
                milestoneId: $scope.milestone_id,
                moved_artifact: $scope.planned[0].id,
                compared_to: $scope.planned[1].id,
                direction: 'before'
            }, ids);
            // ids = [$scope.planned[0].id, $scope.planned[1].id];
        }
    };

//    $scope.change = function(submilestone) {
//        submilestone.$save({milestoneId: submilestone.id});
//    }
};
