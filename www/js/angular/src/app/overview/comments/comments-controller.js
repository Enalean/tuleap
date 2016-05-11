angular
    .module('tuleap.pull-request')
    .controller('CommentsController', CommentsController);

CommentsController.$inject = [
    'lodash',
    'SharedPropertiesService',
    'CommentsRestService',
    'CommentsService'
];

function CommentsController(
    lodash,
    SharedPropertiesService,
    CommentsRestService,
    CommentsService
) {
    var self = this;

    lodash.extend(self, {
        pull_request    : {},
        comments        : [],
        loading_comments: true,
        new_comment     : {
            content: '',
            user_id: SharedPropertiesService.getUserId()
        },
        addComment: addComment
    });

    SharedPropertiesService.whenReady().then(function() {
        self.pull_request = SharedPropertiesService.getPullRequest();
        getComments(CommentsService.comments_pagination.limit, CommentsService.comments_pagination.offset);
    });

    function getComments(limit, offset) {
        return CommentsService.getFormattedComments(self.pull_request, limit, offset)
            .then(function(response) {
                self.comments.push.apply(self.comments, response.data);

                var headers = response.headers();
                var total   = headers['x-pagination-size'];

                if ((limit + offset) < total) {
                    return getComments(limit, offset + limit);
                }

                return self.comments;
            }).finally(function() {
                self.loading_comments = false;
            });
    }

    function addComment(new_comment) {
        CommentsRestService.addComment(self.pull_request.id, new_comment).then(function(comment) {
            CommentsService.formatComment(comment, self.pull_request);

            self.comments.push(comment);
            self.new_comment.content = '';
        });
    }
}
