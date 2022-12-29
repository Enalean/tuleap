import { RelativeDateHelper } from "../../helpers/date-helpers";
import { PullRequestCommentController } from "../../comments/PullRequestCommentController";
import { PullRequestCommentReplyFormFocusHelper } from "../../comments/PullRequestCommentReplyFormFocusHelper";
import { PullRequestCommentRepliesStore } from "../../comments/PullRequestCommentRepliesStore";
import { PullRequestPresenter } from "../../comments/PullRequestPresenter";
import { PullRequestCurrentUserPresenter } from "../../comments/PullRequestCurrentUserPresenter";
import { PullRequestCommentNewReplySaver } from "../../comments/PullRequestCommentReplySaver";

export default TimelineController;

TimelineController.$inject = ["SharedPropertiesService", "TimelineService"];

function TimelineController(SharedPropertiesService, TimelineService) {
    const self = this;

    Object.assign(self, {
        pull_request: {},
        pull_request_presenter: {},
        timeline: [],
        loading_timeline: true,
        new_comment: {
            content: "",
            user_id: SharedPropertiesService.getUserId(),
        },
        relative_date_helper: RelativeDateHelper(
            SharedPropertiesService.getDateTimeFormat(),
            SharedPropertiesService.getRelativeDateDisplay(),
            SharedPropertiesService.getUserLocale()
        ),
        current_user: PullRequestCurrentUserPresenter.fromUserInfo(
            SharedPropertiesService.getUserId(),
            SharedPropertiesService.getUserAvatarUrl()
        ),
        comment_controller: {},
        comment_replies_store: {},
        addComment,
        $onInit: init,
    });

    function init() {
        SharedPropertiesService.whenReady().then(function () {
            self.pull_request = SharedPropertiesService.getPullRequest();
            self.pull_request_presenter = PullRequestPresenter.fromPullRequest(self.pull_request);
            TimelineService.getTimeline(
                self.pull_request,
                TimelineService.timeline_pagination.limit,
                TimelineService.timeline_pagination.offset
            )
                .then(function (timeline) {
                    self.comment_replies_store = PullRequestCommentRepliesStore(timeline);
                    self.timeline = self.comment_replies_store.getAllRootComments();
                    self.comment_controller = PullRequestCommentController(
                        PullRequestCommentReplyFormFocusHelper(),
                        self.comment_replies_store,
                        PullRequestCommentNewReplySaver(),
                        self.current_user,
                        PullRequestPresenter.fromPullRequest(self.pull_request)
                    );
                })
                .finally(function () {
                    self.loading_timeline = false;
                });
        });
    }

    function addComment() {
        TimelineService.addComment(
            self.pull_request,
            self.timeline,
            self.comment_replies_store,
            self.new_comment
        ).then(function () {
            self.new_comment.content = "";
        });
    }
}
