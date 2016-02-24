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
        comments_pagination : {
            limit : 50,
            offset: 0
        },
        getFormattedComments: getFormattedComments,
        formatComment       : formatComment
    });

    function getFormattedComments(pull_request_id, comment_pagination_limit, comment_pagination_offset) {
        return CommentsRestService.getComments(pull_request_id, comment_pagination_limit, comment_pagination_offset).then(function(response) {
            lodash.forEach(response.data, function(comment) {
                self.formatComment(comment);
            });

            return response;
        });
    }

    function formatComment(comment) {
        comment.content = comment.content.replace(/(?:\r\n|\r|\n)/g, '<br/>');
        comment.content = comment.content.replace(/ /g, '&nbsp;');
        comment.content = $sce.trustAsHtml(comment.content);
    }
}
