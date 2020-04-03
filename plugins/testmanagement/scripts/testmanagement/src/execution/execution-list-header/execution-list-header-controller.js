import "../../campaign/campaign-edit.tpl.html";
import CampaignEditCtrl from "../../campaign/campaign-edit-controller.js";

import "../execution-presences.tpl.html";
import ExecutionPresencesCtrl from "../execution-presences-controller.js";

import { setSuccess, setError, resetError } from "../../feedback-state.js";
import { isDefined } from "angular";

controller.$inject = [
    "gettextCatalog",
    "TlpModalService",
    "ExecutionService",
    "SharedPropertiesService",
    "CampaignService",
];

export default function controller(
    gettextCatalog,
    TlpModalService,
    ExecutionService,
    SharedPropertiesService,
    CampaignService
) {
    const self = this;
    Object.assign(self, {
        openEditCampaignModal,
        showPresencesModal,
        isRealtimeEnabled,
        positiveScore,
        isAutomatedTestButtonShown,
        launchAutomatedTests,
        campaign_state: ExecutionService,
        triggered: false,
    });

    function openEditCampaignModal() {
        return TlpModalService.open({
            templateUrl: "campaign-edit.tpl.html",
            controller: CampaignEditCtrl,
            controllerAs: "edit_modal",
            resolve: {
                editCampaignCallback: (campaign) => {
                    ExecutionService.updateCampaign(campaign);
                    ExecutionService.synchronizeExecutions(ExecutionService.campaign.id).then(
                        self.handleRemovedExecutionsCallback
                    );
                },
            },
        });
    }

    function showPresencesModal() {
        return TlpModalService.open({
            templateUrl: "execution-presences.tpl.html",
            controller: ExecutionPresencesCtrl,
            controllerAs: "modal",
            resolve: {
                modal_model: {
                    title: ExecutionService.campaign.label,
                    presences: ExecutionService.presences_on_campaign,
                },
            },
        });
    }

    function isRealtimeEnabled() {
        return SharedPropertiesService.getNodeServerAddress();
    }

    function positiveScore(score) {
        return score ? Math.max(score, 0) : false;
    }

    function isAutomatedTestButtonShown() {
        return (
            isDefined(ExecutionService.campaign.job_configuration) &&
            ExecutionService.campaign.job_configuration.url !== ""
        );
    }

    function launchAutomatedTests() {
        self.triggered = true;
        resetError();
        return CampaignService.triggerAutomatedTests(ExecutionService.campaign.id)
            .then(
                () => {
                    return setSuccess(
                        gettextCatalog.getString(
                            "The job at URL {{ job_url }} has been succesfully launched.",
                            {
                                job_url: ExecutionService.campaign.job_configuration.url,
                            }
                        )
                    );
                },
                (error) => {
                    setError(error.message);
                }
            )
            .finally(() => {
                self.triggered = false;
            });
    }
}
