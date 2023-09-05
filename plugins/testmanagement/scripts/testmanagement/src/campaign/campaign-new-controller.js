export default CampaignNewCtrl;

CampaignNewCtrl.$inject = [
    "modal_instance",
    "$state",
    "CampaignService",
    "DefinitionService",
    "SharedPropertiesService",
];

function CampaignNewCtrl(
    modal_instance,
    $state,
    CampaignService,
    DefinitionService,
    SharedPropertiesService,
) {
    var project_id = SharedPropertiesService.getProjectId(),
        milestone_id = SharedPropertiesService.getCurrentMilestone().id;

    var self = this;

    Object.assign(self, {
        $onInit,
        createCampaign,
        submitting_campaign: false,
        has_milestone: Boolean(milestone_id),
        campaign: {
            label: "",
        },
        test_params: {
            selector: "all",
        },
        test_reports: [],
    });

    function $onInit() {
        getDefinitionReports();
    }

    function createCampaign() {
        self.submitting_campaign = true;

        var campaign_data = {
            project_id: project_id,
            label: self.campaign.label,
        };

        var test_selector = self.test_params.selector;
        var report_id = null;

        if (!isNaN(self.test_params.selector)) {
            test_selector = "report";
            report_id = self.test_params.selector;
        }

        CampaignService.createCampaign(campaign_data, test_selector, milestone_id, report_id)
            .then(function () {
                modal_instance.tlp_modal.hide();
                $state.go("campaigns.list", {}, { reload: true });
            })
            .finally(function () {
                self.submitting_campaign = false;
            });
    }

    function getDefinitionReports() {
        DefinitionService.getDefinitionReports().then(function (data) {
            // data: [{id: <int>, label: <string>}]
            self.test_reports = data;
        });
    }
}
