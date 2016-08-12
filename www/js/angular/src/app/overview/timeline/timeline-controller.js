angular
    .module('tuleap.pull-request')
    .controller('TimelineController', TimelineController);

TimelineController.$inject = [
    'lodash',
    'SharedPropertiesService',
    'TimelineService',
    'TooltipService'
];

function TimelineController(
    lodash,
    SharedPropertiesService,
    TimelineService,
    TooltipService
) {
    var self = this;

    lodash.extend(self, {
        pull_request    : {},
        timeline        : [],
        loading_timeline: true,
        new_comment     : {
            content: '',
            user_id: SharedPropertiesService.getUserId()
        },
        addComment: addComment
    });

    SharedPropertiesService.whenReady().then(function() {
        self.pull_request = SharedPropertiesService.getPullRequest();
        TimelineService.getTimeline(
            self.pull_request,
            TimelineService.timeline_pagination.limit,
            TimelineService.timeline_pagination.offset
        ).then(function(timeline) {
            self.timeline = timeline;
            TooltipService.setupTooltips();
        }).finally(function() {
            self.loading_timeline = false;
        });
    });

    function addComment() {
        TimelineService.addComment(
            self.pull_request,
            self.timeline,
            self.new_comment
        ).then(function() {
            self.new_comment.content = '';
            TooltipService.setupTooltips();
        });
    }
}
