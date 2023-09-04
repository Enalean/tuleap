controller.$inject = ["$scope"];

export default function controller($scope) {
    const self = this;
    Object.assign(self, {
        $onInit: init,
    });
    function init() {
        $scope.$watch(
            () => self.execution.definition.steps,
            (steps) => {
                if (steps !== undefined) {
                    steps.sort((a, b) => a.rank - b.rank);
                }
            },
        );
    }
}
