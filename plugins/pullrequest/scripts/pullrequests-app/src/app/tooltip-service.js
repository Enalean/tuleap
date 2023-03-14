import { loadTooltips } from "@tuleap/tooltip";

export default TooltipService;

TooltipService.$inject = ["$timeout"];

function TooltipService($timeout) {
    const self = this;

    self.setupTooltips = function (element) {
        $timeout(function () {
            loadTooltips(element, false);
        }, 0);
    };
}
