controller.$inject = [];

export default function controller() {
    const self = this;
    Object.assign(self, {
        steps: [],
        $onInit: init
    });

    function init() {
        self.steps = self.execution.definition.steps.map(step => {
            const step_with_result = Object.assign({}, step);
            const step_result = self.execution.steps_results[step.id];

            step_with_result.status = step_result !== undefined ? step_result.status : 'notrun';
            return step_with_result;
        });
        self.steps.sort((a, b) => a.rank - b.rank);
    }
}
