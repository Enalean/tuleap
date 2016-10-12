angular
    .module('kanban')
    .factory('d3', d3Factory);

d3Factory.$inject = [
    '$window'
];

function d3Factory(
    $window
) {
    var d3 = $window.d3;

    return d3;
}
