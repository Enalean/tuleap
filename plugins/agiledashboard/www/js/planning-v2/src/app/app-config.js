export default PlanningConfig;

PlanningConfig.$inject = ["$animateProvider"];

function PlanningConfig($animateProvider) {
    // Without this setting, ngAnimate will add classnames on every drag
    // and drop which hurts performance. We only need ngAnimate on
    // specific places.
    $animateProvider.classNameFilter(/do-animate/);
}
