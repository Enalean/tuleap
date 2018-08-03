import "./campaign-new.tpl.html";

import { getCampaigns } from "../api/rest-querier.js";
import { setError } from "../feedback-state.js";

export default CampaignListCtrl;

CampaignListCtrl.$inject = [
    "$q",
    "$scope",
    "$filter",
    "gettextCatalog",
    "TlpModalService",
    "SharedPropertiesService",
    "milestone"
];

function CampaignListCtrl(
    $q,
    $scope,
    $filter,
    gettextCatalog,
    TlpModalService,
    SharedPropertiesService,
    milestone
) {
    const self = this;
    const project_id = SharedPropertiesService.getProjectId();

    Object.assign($scope, {
        loading: true,
        campaigns: [],
        filtered_campaigns: [],
        has_open_campaigns: false,
        has_closed_campaigns: false,
        campaigns_loaded: false,
        closed_campaigns_hidden: true,
        shouldShowNoCampaigns,
        shouldShowNoOpenCampaigns,
        showClosedCampaigns,
        hideClosedCampaigns,
        openNewCampaignModal
    });

    Object.assign(self, {
        $onInit: init
    });

    function init() {
        return loadCampaigns(project_id);
    }

    function loadCampaigns(project_id) {
        $scope.loading = true;

        return $q
            .when(getCampaigns(project_id, milestone.id, "open"))
            .then(campaigns => {
                $scope.campaigns = $scope.campaigns.concat(campaigns);
                $scope.filtered_campaigns = filterCampaigns($scope.campaigns, "open");
                $scope.has_open_campaigns = $scope.filtered_campaigns.length > 0;

                return $q.when(getCampaigns(project_id, milestone.id, "closed"));
            })
            .then(campaigns => {
                $scope.campaigns = $scope.campaigns.concat(campaigns);
                $scope.has_closed_campaigns =
                    filterCampaigns($scope.campaigns, "closed").length > 0;

                $scope.campaigns_loaded = true;
            })
            .catch(e => {
                if (!e.response) {
                    throw e;
                }

                return e.response.json().then(({ error }) => {
                    const message = error.message;
                    setError(
                        gettextCatalog.getString(
                            "An error occurred while loading the campaigns. Please refresh the page. {{ message }}",
                            { message }
                        )
                    );
                });
            })
            .finally(() => {
                $scope.loading = false;
            });
    }

    function shouldShowNoCampaigns() {
        return $scope.campaigns_loaded && $scope.campaigns.length === 0;
    }

    function shouldShowNoOpenCampaigns() {
        return (
            $scope.closed_campaigns_hidden &&
            $scope.campaigns_loaded &&
            !$scope.has_open_campaigns &&
            $scope.has_closed_campaigns
        );
    }

    function showClosedCampaigns() {
        $scope.filtered_campaigns = $scope.campaigns;
        $scope.closed_campaigns_hidden = false;
    }

    function hideClosedCampaigns() {
        $scope.filtered_campaigns = filterCampaigns($scope.campaigns, "open");
        $scope.closed_campaigns_hidden = true;
    }

    function filterCampaigns(list, status) {
        if (status === null) {
            return list;
        }

        return $filter("filter")(list, { status: status });
    }

    function openNewCampaignModal() {
        return TlpModalService.open({
            templateUrl: "campaign-new.tpl.html",
            controller: "CampaignNewCtrl",
            controllerAs: "campaign_modal"
        });
    }
}
