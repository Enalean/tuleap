angular
    .module('campaign')
    .controller('CampaignNewCtrl', CampaignNewCtrl);

CampaignNewCtrl.$inject = [
    '$scope',
    '$modalInstance',
    '$state',
    'gettextCatalog',
    'CampaignService',
    'DefinitionService',
    'SharedPropertiesService'
];

function CampaignNewCtrl(
    $scope,
    $modalInstance,
    $state,
    gettextCatalog,
    CampaignService,
    DefinitionService,
    SharedPropertiesService
) {
    var project_id              = SharedPropertiesService.getProjectId(),
        milestone_id            = SharedPropertiesService.getCurrentMilestone().id,
        controller_is_destroyed = false;

    _.extend($scope, {
        nb_total_definitions: 0,
        loading_definitions:  true,
        submitting_campaign:  false,
        definitions:          [],
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

    $scope.$on('$destroy', function iVeBeenDismissed() {
        controller_is_destroyed = true;
    });

    function init() {
        getDefinitions(project_id, 750, 0);
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

    function getDefinitions(project_id, limit, offset) {
        DefinitionService.getDefinitions(project_id, limit, offset).then(function(data) {
            $scope.definitions = $scope.definitions.concat(data.results);
            $scope.nb_total_definitions = data.total;

            if (! controller_is_destroyed && $scope.definitions.length < $scope.nb_total_definitions) {
                getDefinitions(project_id, limit, offset + limit);
            } else {
                $scope.loading_definitions = false;
            }
        });
    }

    function getDefinitionReports() {
        DefinitionService.getDefinitionReports().then(function(data) {
            // data: [{id: <int>, label: <string>}]
            $scope.test_reports = data;
        });
    }
}
