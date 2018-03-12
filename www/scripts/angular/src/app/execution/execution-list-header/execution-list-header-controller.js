import '../../campaign/campaign-edit.tpl.html';
import CampaignEditCtrl from '../../campaign/campaign-edit-controller.js';

import '../execution-presences.tpl.html';
import ExecutionPresencesCtrl from '../execution-presences-controller.js';

controller.$inject = [
    'TlpModalService',
    'ExecutionService',
    'SharedPropertiesService',
];

export default function controller(
    TlpModalService,
    ExecutionService,
    SharedPropertiesService,
) {
    const self = this;
    Object.assign(self, {
        openEditCampaignModal,
        showPresencesModal,
        isRealtimeEnabled,
        positiveScore,
        campaign_state: ExecutionService
    });

    function openEditCampaignModal() {
        return TlpModalService.open({
            templateUrl :  'campaign-edit.tpl.html',
            controller  :  CampaignEditCtrl,
            controllerAs: 'edit_modal',
            resolve     : {
                editCampaignCallback: campaign => {
                    ExecutionService.updateCampaign(campaign);
                    ExecutionService
                        .synchronizeExecutions(ExecutionService.campaign.id)
                        .then(self.handleRemovedExecutionsCallback);
                }
            }
        });
    }

    function showPresencesModal() {
        return TlpModalService.open({
            templateUrl : 'execution-presences.tpl.html',
            controller  : ExecutionPresencesCtrl,
            controllerAs: 'modal',
            resolve     : {
                modal_model: {
                    title:     ExecutionService.campaign.label,
                    presences: ExecutionService.presences_on_campaign
                }
            }
        });
    }

    function isRealtimeEnabled() {
        return SharedPropertiesService.getNodeServerAddress();
    }

    function positiveScore(score) {
        return score ? Math.max(score, 0) : '-';
    }
}
