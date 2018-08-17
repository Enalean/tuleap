export default PlanningConfig;

PlanningConfig.$inject = ["$compileProvider", "$animateProvider"];

function PlanningConfig($compileProvider, $animateProvider) {
    // Without this setting, ngAnimate will add classnames on every drag
    // and drop which hurts performance. We only need ngAnimate on
    // specific places.
    $animateProvider.classNameFilter(/do-animate/);

    // To remove this setting, move all init() code
    // of directive controllers to $onInit
    $compileProvider.preAssignBindingsEnabled(true);
}
