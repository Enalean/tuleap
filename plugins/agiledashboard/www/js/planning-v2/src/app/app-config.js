export default PlanningConfig;

PlanningConfig.$inject = [
    '$animateProvider'
];

function PlanningConfig(
    $animateProvider
) {
    $animateProvider.classNameFilter(/do-animate/);
}
