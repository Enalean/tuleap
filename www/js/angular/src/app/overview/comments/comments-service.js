angular
    .module('tuleap.pull-request')
    .service('CommentsService', CommentsService);

CommentsService.$inject = [
    'lodash',
    '$sce',
    'CommentsRestService'
];

function CommentsService(
    lodash,
    $sce,
    CommentsRestService
) {
    var self = this;

    lodash.extend(self, {
        comments_pagination: {
            limit : 50,
            offset: 0
        },
        getFormattedComments: getFormattedComments,
        formatComment       : formatComment,
        markAuthor          : markAuthor
    });

    function getFormattedComments(pull_request, comment_pagination_limit, comment_pagination_offset) {
        return CommentsRestService.getComments(pull_request.id, comment_pagination_limit, comment_pagination_offset).then(function(response) {
            lodash.forEach(response.data, function(comment) {
                self.formatComment(comment, pull_request);
            });

            return response;
        });
    }

    function formatComment(comment, pull_request) {
        comment.content = comment.content.replace(/(?:\r\n|\r|\n)/g, '<br/>');
        comment.content = $sce.trustAsHtml(comment.content);
        self.markAuthor(comment, pull_request.user_id);
    }

    function markAuthor(comment, prAuthorId) {
        comment.isFromPRAuthor = comment.user.id === prAuthorId;
    }
}
