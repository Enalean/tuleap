import { PullRequestCurrentUserPresenterBuilder } from "../../comments/PullRequestCurrentUserPresenterBuilder";
import { PullRequestPresenterBuilder } from "../../comments/PullRequestPresenterBuilder";
import {
    PullRequestCommentController,
    PullRequestCommentTextareaFocusHelper,
    PullRequestCommentRepliesStore,
    PullRequestCommentNewReplySaver,
} from "@tuleap/plugin-pullrequest-comments";

export default TimelineController;

TimelineController.$inject = ["SharedPropertiesService", "TimelineService"];

function TimelineController(SharedPropertiesService, TimelineService) {
    const self = this;

    Object.assign(self, {
        pull_request: {},
        timeline: [],
        loading_timeline: true,
        new_comment: {
            content: "",
            user_id: SharedPropertiesService.getUserId(),
        },
        comment_controller: {},
        comment_replies_store: {},
        addComment,
        $onInit: init,
    });

    function init() {
        SharedPropertiesService.whenReady().then(function () {
            self.pull_request = SharedPropertiesService.getPullRequest();
            TimelineService.getTimeline(
                self.pull_request,
                TimelineService.timeline_pagination.limit,
                TimelineService.timeline_pagination.offset
            )
                .then(function (timeline) {
                    self.comment_replies_store = PullRequestCommentRepliesStore(timeline);
                    self.timeline = self.comment_replies_store.getAllRootComments();
                    self.comment_controller = PullRequestCommentController(
                        PullRequestCommentTextareaFocusHelper(),
                        self.comment_replies_store,
                        PullRequestCommentNewReplySaver(),
                        PullRequestCurrentUserPresenterBuilder.fromUserInfo(
                            SharedPropertiesService.getUserId(),
                            SharedPropertiesService.getUserAvatarUrl(),
                            SharedPropertiesService.getUserLocale(),
                            SharedPropertiesService.getDateTimeFormat(),
                            SharedPropertiesService.getRelativeDateDisplay()
                        ),
                        PullRequestPresenterBuilder.fromPullRequest(self.pull_request)
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
