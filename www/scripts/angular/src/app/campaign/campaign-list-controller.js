angular
    .module('campaign')
    .controller('CampaignListCtrl', CampaignListCtrl);

CampaignListCtrl.$inject = ['$scope', 'CampaignService', 'SharedPropertiesService'];

function CampaignListCtrl($scope, CampaignService, SharedPropertiesService) {
    var project_id      = SharedPropertiesService.getProjectId();
    var total_campaigns = 0;

    _.extend($scope, {
        loading                       : true,
        campaigns                     : [],
        campaigns_closed              : [],
        open_campaigns_loaded         : false,
        closed_campaigns_loaded       : false,
        closed_campaigns_hidden       : false,
        getClosedCampaigns            : getClosedCampaigns,
        hideClosedCampaigns           : hideClosedCampaigns
    });

    getCampaigns(project_id, 'open', 10, 0);

    function getCampaigns(project_id, campaign_status, limit, offset) {
        CampaignService.getCampaigns(project_id, campaign_status, limit, offset).then(function(data) {
            $scope.campaigns        = $scope.campaigns.concat(data.results);
            total_campaigns         = data.total;

            if ($scope.campaigns.length < total_campaigns) {
                getCampaigns(project_id, campaign_status, limit, offset + limit);
            } else {
                $scope.loading = false;

                if (campaign_status === 'closed') {
                    $scope.campaigns_closed        = data.results;
                    $scope.closed_campaigns_loaded = true;
                }

                if (campaign_status === 'open') {
                    $scope.open_campaigns_loaded = true;
                }
            }
        });
    }

    function getClosedCampaigns() {
        if (! $scope.closed_campaigns_loaded) {
            $scope.loading = true;
            getCampaigns(project_id, 'closed', 10, 0);
        } else {
            $scope.campaigns               = $scope.campaigns.concat($scope.campaigns_closed);
            $scope.closed_campaigns_hidden = false;
        }
    }

    function hideClosedCampaigns() {
        $scope.campaigns               = _.xor($scope.campaigns, $scope.campaigns_closed);
        $scope.closed_campaigns_hidden = true;
    }
}