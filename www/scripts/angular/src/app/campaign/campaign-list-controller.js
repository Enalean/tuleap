angular
    .module('campaign')
    .controller('CampaignListCtrl', CampaignListCtrl);

CampaignListCtrl.$inject = [
    '$scope',
    '$modal',
    '$filter',
    'CampaignService',
    'SharedPropertiesService',
    'milestone'
];

function CampaignListCtrl(
    $scope,
    $modal,
    $filter,
    CampaignService,
    SharedPropertiesService,
    milestone
) {
    var project_id = SharedPropertiesService.getProjectId();

    _.extend($scope, {
        loading                       : true,
        campaigns                     : [],
        filtered_campaigns            : [],
        has_open_campaigns            : false,
        has_closed_campaigns          : false,
        campaigns_loaded              : false,
        closed_campaigns_hidden       : true,
        shouldShowNoCampaigns         : shouldShowNoCampaigns,
        shouldShowNoOpenCampaigns     : shouldShowNoOpenCampaigns,
        showClosedCampaigns           : showClosedCampaigns,
        hideClosedCampaigns           : hideClosedCampaigns,
        openNewCampaignModal          : openNewCampaignModal
    });

    init(project_id);

    function init(project_id) {
        loadCampaigns(project_id, 10, 0);
    }

    function getCampaigns(project_id, milestone_id, campaign_status, limit, offset) {
        return CampaignService
            .getCampaigns(project_id, milestone_id, campaign_status, limit, offset)
            .then(function(data) {
                $scope.campaigns = $scope.campaigns.concat(data.results);

                if (filterCampaigns($scope.campaigns, campaign_status).length < data.total) {
                    return getCampaigns(project_id, milestone_id, campaign_status, limit, offset + limit);
                }
            });
    }

    function loadCampaigns(project_id, limit, offset) {
        $scope.loading = true;

        getCampaigns(project_id, milestone.id, 'open', limit, offset)
        .then(function() {
            $scope.filtered_campaigns = filterCampaigns($scope.campaigns, 'open');
            $scope.has_open_campaigns = $scope.filtered_campaigns.length > 0;

            return getCampaigns(project_id, milestone.id, 'closed', limit, offset);
        })
        .then(function() {
            $scope.has_closed_campaigns = filterCampaigns($scope.campaigns, 'closed').length > 0;
            $scope.campaigns_loaded = true;
            $scope.loading = false;
        });
    }

    function shouldShowNoCampaigns() {
        return $scope.campaigns_loaded && $scope.campaigns.length === 0;
    }

    function shouldShowNoOpenCampaigns() {
        return $scope.closed_campaigns_hidden &&
               $scope.campaigns_loaded &&
               ! $scope.has_open_campaigns &&
               $scope.has_closed_campaigns;
    }

    function showClosedCampaigns() {
        $scope.filtered_campaigns      = $scope.campaigns;
        $scope.closed_campaigns_hidden = false;
    }

    function hideClosedCampaigns() {
        $scope.filtered_campaigns      = filterCampaigns($scope.campaigns, 'open');
        $scope.closed_campaigns_hidden = true;
    }

    function filterCampaigns(list, status) {
        if (status === null) {
          return list;
        }

        return $filter('filter')(list, { 'status': status });
    }

    function openNewCampaignModal() {
        return $modal.open({
            templateUrl: 'campaign/campaign-new.tpl.html',
            controller : 'CampaignNewCtrl',
        });
    }

}
