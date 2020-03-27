import { getSuccess, setSuccess } from "./success-state.js";

controller.$inject = ["$timeout", "$scope"];

function controller($timeout, $scope) {
    this.getSuccess = getSuccess;
    this.$onInit = () => {
        $scope.$watch("$ctrl.getSuccess()", (new_value) => {
            if (new_value) {
                $timeout(() => setSuccess(null), 5000);
            }
        });
    };
}

export default {
    template:
        '<div class="tlp-alert-success planning-success" ng-if="$ctrl.getSuccess()">{{ $ctrl.getSuccess() }}</div>',
    controller,
};
