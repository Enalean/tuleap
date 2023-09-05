/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

export default CampaignEditLabelCtrl;

CampaignEditLabelCtrl.$inject = [
    "modal_instance",
    "$scope",
    "$q",
    "$state",
    "SharedPropertiesService",
    "CampaignService",
    "editCampaignLabelCallback",
];

function CampaignEditLabelCtrl(
    modal_instance,
    $scope,
    $q,
    $state,
    SharedPropertiesService,
    CampaignService,
    editCampaignLabelCallback,
) {
    let campaign_id;
    const self = this;
    Object.assign(self, {
        $onInit: init,
    });

    Object.assign($scope, {
        editCampaignLabel,
    });

    function init() {
        campaign_id = $state.params.id;

        SharedPropertiesService.setCampaignId(campaign_id);

        CampaignService.getCampaign(campaign_id).then((campaign) => {
            $scope.campaign = campaign;
        });
    }

    function editCampaignLabel(campaign) {
        $scope.submitting_changes = true;

        CampaignService.patchCampaign(campaign.id, campaign.label, campaign.job_configuration).then(
            (response) => {
                $scope.submitting_changes = false;

                if (editCampaignLabelCallback) {
                    editCampaignLabelCallback(response);
                }

                modal_instance.tlp_modal.hide();
            },
        );
    }
}
