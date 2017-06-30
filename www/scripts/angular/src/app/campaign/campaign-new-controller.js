angular
    .module('campaign')
    .controller('CampaignNewCtrl', CampaignNewCtrl);

CampaignNewCtrl.$inject = [
    '$scope',
    '$modalInstance',
    '$state',
    'CampaignService',
    'DefinitionService',
    'SharedPropertiesService'
];

function CampaignNewCtrl(
    $scope,
    $modalInstance,
    $state,
    CampaignService,
    DefinitionService,
    SharedPropertiesService
) {
    var project_id   = SharedPropertiesService.getProjectId(),
        milestone_id = SharedPropertiesService.getCurrentMilestone().id;

    _.extend($scope, {
        submitting_campaign:  false,
        createCampaign:       createCampaign,
        cancel:               cancel,
        has_milestone:        !! milestone_id,
        campaign: {
            label:    ''
        },
        test_params: {
            selector: 'all'
        },
        test_reports:  []
    });

    init();

    function init() {
        getDefinitionReports();
    }

    function createCampaign(campaign, test_params) {
        $scope.submitting_campaign = true;

        var campaign_data = {
            project_id: project_id,
            label:      campaign.label,
        };

        var test_selector = test_params.selector;
        var report_id     = null;

        if (! isNaN(test_params.selector)) {
            test_selector = 'report';
            report_id     = test_params.selector;
        }

        CampaignService
            .createCampaign(campaign_data, test_selector, milestone_id, report_id)
            .then(function () {
                $modalInstance.close();
                $state.go('campaigns.list', {}, {reload: true});
            })
            .finally(function () {
                $scope.submitting_campaign = false;
            });
    }

    function cancel() {
        $modalInstance.dismiss();
    }

    function getDefinitionReports() {
        DefinitionService.getDefinitionReports().then(function(data) {
            // data: [{id: <int>, label: <string>}]
            $scope.test_reports = data;
        });
    }
}
