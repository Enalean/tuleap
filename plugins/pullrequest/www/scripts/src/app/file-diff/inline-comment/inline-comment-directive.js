import "./inline-comment.tpl.html";

export default InlineCommentDirective;

function InlineCommentDirective() {
    return {
        restrict: "AE",
        scope: {
            comment: "="
        },
        templateUrl: "inline-comment.tpl.html"
    };
}
