export default controller;

controller.$inject = [];

function controller() {
    const self = this;
    Object.assign(self, {
        getStyle,
        $onChanges,
    });

    function $onChanges(changes) {
        const { max_value, value } = changes;
        if (max_value || value) {
            self.style = getStyle();
        }
    }

    function getStyle() {
        const width = getWidthPercentage();
        return { width: `${width}%` };
    }

    function getWidthPercentage() {
        if (
            !isNumber(self.value) ||
            self.value < 0 ||
            !isNumber(self.max_value) ||
            self.max_value <= 0
        ) {
            return 0;
        }

        const progress = self.max_value - self.value;
        const clamped_progress = clamp(progress, 0, self.max_value);
        return (clamped_progress / self.max_value) * 100;
    }

    function clamp(value, min, max) {
        return Math.min(Math.max(min, value), max);
    }

    function isNumber(n) {
        return !Number.isNaN(Number.parseFloat(n)) && isFinite(n);
    }
}
