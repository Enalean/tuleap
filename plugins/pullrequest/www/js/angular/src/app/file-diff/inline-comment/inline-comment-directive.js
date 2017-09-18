angular
    .module('tuleap.pull-request')
    .directive('inlineComment', InlineCommentDirective);

function InlineCommentDirective() {
    return {
        restrict   : 'AE',
        scope      : {
            comment: '='
        },
        templateUrl: 'file-diff/inline-comment/inline-comment.tpl.html'
    };
}
