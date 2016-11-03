angular
    .module('kanban')
    .filter('tuleapStripTags', TuleapStripTagsFilter);

TuleapStripTagsFilter.$inject = ['$window'];

function TuleapStripTagsFilter($window) {
    return function(html) {
        return $window.striptags(html);
    };
}
