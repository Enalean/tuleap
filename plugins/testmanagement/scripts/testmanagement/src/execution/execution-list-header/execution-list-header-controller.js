import "../../campaign/campaign-edit.tpl.html";
import CampaignEditCtrl from "../../campaign/campaign-edit-controller.js";

import "../../campaign/campaign-edit-label.tpl.html";
import CampaignEditLabelCtrl from "../../campaign/campaign-edit-label-controller.js";

import "../../campaign/download/download-error.tpl.html";
import DownloadErrorCtrl from "../../campaign/download/download-error-controller";

import "../../campaign/campaign-edit-automated.tpl.html";
import CampaignEditAutomatedCtrl from "../../campaign/campaign-edit-automated-controller.js";

import "../execution-presences.tpl.html";
import ExecutionPresencesCtrl from "../execution-presences-controller.js";

import { setSuccess, setError, resetError } from "../../feedback-state.js";
import { isDefined } from "angular";
import { downloadCampaignAsDocx } from "../../campaign/download/download-campaign-as-docx";

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
    CampaignService,
) {
    const self = this;
    Object.assign(self, {
        openEditCampaignModal,
        openEditCampaignLabelModal,
        openEditCampaignAutomatedModal,
        showPresencesModal,
        positiveScore,
        isAutomatedTestButtonShown,
        launchAutomatedTests,
        campaign_state: ExecutionService,
        triggered: false,
        getOpenFormURL,
        getCloseFormURL,
        getCSRFTokenCampaignStatus,
        getCurrentMilestone,
        exportCampaignAsDocument,
        is_preparing_the_download: false,
    });

    async function exportCampaignAsDocument() {
        if (self.is_preparing_the_download) {
            return;
        }
        self.is_preparing_the_download = true;

        try {
            await downloadCampaignAsDocx(
                ExecutionService.campaign,
                SharedPropertiesService.getPlatformName(),
                SharedPropertiesService.getPlatformLogoUrl(),
                SharedPropertiesService.getProjectName(),
                SharedPropertiesService.getCurrentUser().display_name,
                SharedPropertiesService.getUserTimezone(),
                SharedPropertiesService.getUserLocale(),
                SharedPropertiesService.getBaseUrl(),
                SharedPropertiesService.getProjectId(),
                SharedPropertiesService.getDefinitionTrackerId() || null,
                SharedPropertiesService.getArtifactLinksTypes() || [],
            );
        } catch (e) {
            TlpModalService.open({
                templateUrl: "download-error.tpl.html",
                controller: DownloadErrorCtrl,
                controllerAs: "error_modal",
            });
            throw e;
        } finally {
            self.is_preparing_the_download = false;
        }
    }

    function openEditCampaignModal() {
        return TlpModalService.open({
            templateUrl: "campaign-edit.tpl.html",
            controller: CampaignEditCtrl,
            controllerAs: "edit_modal",
            resolve: {
                editCampaignCallback: () => {
                    ExecutionService.synchronizeExecutions(ExecutionService.campaign.id).then(
                        self.handleRemovedExecutionsCallback,
                    );
                },
            },
        });
    }

    function openEditCampaignLabelModal() {
        return TlpModalService.open({
            templateUrl: "campaign-edit-label.tpl.html",
            controller: CampaignEditLabelCtrl,
            controllerAs: "edit_label_modal",
            resolve: {
                editCampaignLabelCallback: (campaign) => {
                    ExecutionService.updateCampaign(campaign);
                },
            },
        });
    }

    function openEditCampaignAutomatedModal() {
        return TlpModalService.open({
            templateUrl: "campaign-edit-automated.tpl.html",
            controller: CampaignEditAutomatedCtrl,
            controllerAs: "edit_automated_modal",
            resolve: {
                editCampaignAutomatedCallback: (campaign) => {
                    ExecutionService.updateCampaign(campaign);
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
                            },
                        ),
                    );
                },
                (error) => {
                    setError(error.message);
                },
            )
            .finally(() => {
                self.triggered = false;
            });
    }

    function getOpenFormURL() {
        return "/plugins/testmanagement/campaign/" + self.campaign_state.campaign.id + "/open";
    }

    function getCloseFormURL() {
        return "/plugins/testmanagement/campaign/" + self.campaign_state.campaign.id + "/close";
    }

    function getCSRFTokenCampaignStatus() {
        return SharedPropertiesService.getCSRFTokenCampaignStatus();
    }

    function getCurrentMilestone() {
        return SharedPropertiesService.getCurrentMilestone();
    }
}
