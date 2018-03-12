export default TooltipService;

TooltipService.$inject = [
    '$window',
    '$timeout'
];

function TooltipService(
    $window,
    $timeout
) {
    const self = this;

    self.setupTooltips = function() {
        $timeout(function() {
            $window.codendi.Tooltip.load($window.document.body);
        }, 0);
    };
}
