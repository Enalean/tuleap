angular
    .module('tuleap.pull-request')
    .directive('comments', CommentsDirective);

function CommentsDirective() {
    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'pull-request/comments/comments.tpl.html',
        controller      : 'CommentsController as comments',
        bindToController: true
    };
}
