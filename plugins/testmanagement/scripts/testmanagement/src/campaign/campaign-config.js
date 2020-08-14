import "./campaign-list.tpl.html";
import CampaignCtrl from "./campaign-controller.js";
import CampaignListCtrl from "./campaign-list-controller.js";

export default CampaignConfig;

CampaignConfig.$inject = ["$stateProvider"];

function CampaignConfig($stateProvider) {
    $stateProvider
        .state("campaigns", {
            abstract: true,
            url: "/campaigns",
            template: "<ui-view></ui-view>",
            controller: CampaignCtrl,
            resolve: {
                milestone: [
                    "SharedPropertiesService",
                    function (SharedPropertiesService) {
                        return SharedPropertiesService.getCurrentMilestone();
                    },
                ],
            },
        })
        .state("campaigns.milestone", {
            url: "/milestone",
            ncyBreadcrumb: {
                label: "{{ milestone.label }}",
            },
            onEnter: [
                "$window",
                "milestone",
                function ($window, milestone) {
                    $window.open(milestone.uri, "_self");
                },
            ],
        })
        .state("campaigns.list", {
            url: "",
            controller: CampaignListCtrl,
            controllerAs: "$ctrl",
            templateUrl: "campaign-list.tpl.html",
            ncyBreadcrumb: {
                label: "{{ campaign_breadcrumb_label }}",
                skip: shouldCampaignsListBreadcrumbBeSkipped(),
            },
        });
}

function shouldCampaignsListBreadcrumbBeSkipped() {
    const init_data = document.querySelector(".testmanagement-init-data");
    if (!init_data) {
        return false;
    }

    try {
        const current_milestone = JSON.parse(init_data.dataset.currentMilestone);
        return "id" in current_milestone;
    } catch (e) {
        return false;
    }
}
