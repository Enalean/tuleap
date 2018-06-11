controller.$inject = [];

export default function controller() {
    const self = this;
    Object.assign(self, {
        steps: [],
        $onInit: init
    });

    function init() {
        self.steps = self.execution.definition.steps;
        self.steps.sort((a, b) => a.rank - b.rank);
    }
}
