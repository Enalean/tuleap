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
            total_campaigns  = data.total;
            $scope.campaigns = $scope.campaigns.concat(data.results);
            if (campaign_status === 'closed') {
                $scope.campaigns_closed        = $scope.campaigns_closed.concat(data.results);
                $scope.closed_campaigns_loaded = loadCampaigns(
                    campaign_status,
                    $scope.closed_campaigns_loaded,
                    $scope.campaigns_closed,
                    total_campaigns,
                    limit,
                    offset
                );
            } else if (campaign_status === 'open') {
                $scope.open_campaigns_loaded = loadCampaigns(
                    campaign_status,
                    $scope.open_campaigns_loaded,
                    $scope.campaigns,
                    total_campaigns,
                    limit,
                    offset
                );
            }
        });
    }

    function loadCampaigns(
        campaign_status,
        are_campaigns_loaded,
        campaigns,
        total_campaigns,
        limit,
        offset
    ) {
        if (campaigns.length < total_campaigns) {
            getCampaigns(project_id, campaign_status, limit, offset + limit);
        } else {
            $scope.loading       = false;
            are_campaigns_loaded = true;
        }
        return are_campaigns_loaded;
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