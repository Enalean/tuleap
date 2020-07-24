import "./robot-blocked.tpl.html";
import "./robot-failed.tpl.html";
import "./robot-notrun.tpl.html";
import "./robot-passed.tpl.html";

controller.$inject = ["$scope"];

function controller($scope) {
    const self = this;

    this.$onInit = () => {
        $scope.robot_url = `robot-${self.testStatus}.tpl.html`;
    };
}

export default {
    controller,
    template: '<ng-include src="robot_url"></ng-include>',
    bindings: {
        testStatus: "<",
    },
};
