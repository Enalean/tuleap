import { getSuccess, resetSuccess, getError } from "./feedback-state.js";

controller.$inject = ["$timeout", "$scope"];

function controller($timeout, $scope) {
    Object.assign(this, {
        getSuccess,
        getError,
        $onInit: init,
    });

    function init() {
        $scope.$watch("$ctrl.getSuccess()", (new_value) => {
            if (new_value) {
                $timeout(resetSuccess, 5000);
            }
        });
    }
}

export default {
    template: `
        <div class="tlp-alert-success feedback-success" ng-if="$ctrl.getSuccess()">{{ $ctrl.getSuccess() }}</div>
        <div class="tlp-alert-danger feedback-error" ng-if="$ctrl.getError()">{{ $ctrl.getError() }}</div>
    `,
    controller,
};
