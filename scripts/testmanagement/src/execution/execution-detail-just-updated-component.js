import { isUpdated, resetUpdated } from "./execution-detail-just-updated-state.js";

controller.$inject = ["$timeout", "$scope"];

function controller($timeout, $scope) {
    Object.assign(this, {
        isUpdated,
        $onInit: init,
    });

    function init() {
        $scope.$watch("$ctrl.isUpdated()", (new_value) => {
            if (new_value) {
                $timeout(resetUpdated, 5000);
            }
        });
    }
}

export default {
    template: `
        <div class="tlp-alert-info feedback-info" ng-if="$ctrl.isUpdated()" translate>
            You have just updated the test definition, so it has been reset. Please start over.
        </div>
    `,
    controller,
};
