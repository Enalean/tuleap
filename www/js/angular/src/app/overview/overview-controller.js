angular
    .module('tuleap.pull-request')
    .controller('OverviewController', OverviewController);

OverviewController.$inject = [
    '$q',
    '$state',
    'lodash',
    'SharedPropertiesService',
    'PullRequestService',
    'UserRestService',
    'MergeModalService',
    'TooltipService'
];

function OverviewController(
    $q,
    $state,
    _,
    SharedPropertiesService,
    PullRequestService,
    UserRestService,
    MergeModalService,
    TooltipService
) {
    var self = this;

    _.extend(self, {
        author             : {},
        editionForm        : {},
        operationInProgress: false,
        pull_request       : {},
        showEditionForm    : false,
        valid_status_keys  : PullRequestService.valid_status_keys,

        abandon              : abandon,
        buildStatusIs        : buildStatusIs,
        checkMerge           : checkMerge,
        hasAbandonRight      : hasAbandonRight,
        hasMergeRight        : hasMergeRight,
        isConflictingMerge   : isConflictingMerge,
        isNonFastForwardMerge: isNonFastForwardMerge,
        isUnknownMerge       : isUnknownMerge,
        saveEditionForm      : saveEditionForm
    });

    SharedPropertiesService.whenReady().then(function() {
        self.pull_request = SharedPropertiesService.getPullRequest();

        self.editionForm.raw_title       = self.pull_request.raw_title;
        self.editionForm.raw_description = self.pull_request.raw_description;

        UserRestService.getUser(self.pull_request.user_id).then(function(user) {
            self.author = user;
        });

        TooltipService.setupTooltips();
    })
    .catch(function() {
        $state.go('dashboard');
    });

    function buildStatusIs(status) {
        return self.pull_request.last_build_status === status;
    }

    function saveEditionForm() {
        PullRequestService.updateTitleAndDescription(
            self.pull_request,
            self.editionForm.raw_title,
            self.editionForm.raw_description)
        .then(function() {
            self.showEditionForm = false;
            TooltipService.setupTooltips();
        });
    }

    function isOpen() {
        return self.pull_request.status === self.valid_status_keys.review;
    }

    function isConflictingMerge() {
        return self.pull_request.merge_status === 'conflict' && isOpen();
    }

    function isNonFastForwardMerge() {
        return self.pull_request.merge_status === 'no_fastforward' && isOpen();
    }

    function isUnknownMerge() {
        return self.pull_request.merge_status === 'unknown-merge-status' && isOpen();
    }

    function hasMergeRight() {
        return self.pull_request.user_can_merge && isOpen();
    }

    function hasAbandonRight() {
        return self.pull_request.user_can_abandon && isOpen();
    }

    function checkMerge() {
        var shouldMerge = isNonFastForwardMerge() ? MergeModalService.showMergeModal() : $q.when('go');
        shouldMerge.then(merge);
    }

    function merge() {
        self.operationInProgress = true;
        PullRequestService.merge(self.pull_request).then(function() {
            self.operationInProgress = false;
        });
    }

    function abandon() {
        self.operationInProgress = true;
        PullRequestService.abandon(self.pull_request).then(function() {
            self.operationInProgress = false;
        });
    }
}
