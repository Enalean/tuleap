/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
import { post } from "@tuleap/tlp-fetch";
import { buildEventDispatcher } from "./buildEventDispatcher";
import { FatalError, RealtimeMercure, RetriableError } from "./realtime-mercure";

export default MercureService;

MercureService.$inject = [
    "$timeout",
    "$q",
    "$rootScope",
    "locker",
    "SocketFactory",
    "ExecutionService",
    "SharedPropertiesService",
    "JWTService",
    "ExecutionRestService",
    "CampaignService",
];

function MercureService(
    $timeout,
    $q,
    $rootScope,
    locker,
    SocketFactory,
    ExecutionService,
    SharedPropertiesService,
    JWTService,
    ExecutionRestService,
    CampaignService,
) {
    const self = this;
    Object.assign(self, {
        checkDisconnect: {
            disconnect: false,
        },
        init,
        getToken,
        requestJWTToRefreshToken,
    });
    let realtime_mercure;
    let realtime_token;

    let mercureRetryNumber = 0;
    let executionDeletedCallback;
    let campaignUpdatedCallback;
    let campaignId;

    function init(campaign_id, execution_deleted_callback, campaign_updated_callback) {
        campaignId = campaign_id;
        executionDeletedCallback = execution_deleted_callback;
        campaignUpdatedCallback = campaign_updated_callback;
        this.getToken(campaign_id).then((data) => {
            realtime_token = data;
            realtime_mercure = new RealtimeMercure(
                realtime_token,
                "/.well-known/mercure?topic=TestManagement/" + campaign_id,
                buildEventDispatcher(
                    executionCreated,
                    executionUpdated,
                    executionDeleted,
                    artifactLinked,
                    campaignUpdated,
                    presenceUpdated,
                ),
                errCallback,
                sucessCallback,
            );
        });
    }

    function getToken(id) {
        return $q.when(
            post(encodeURI(`/plugins/testmanagement/mercure_realtime_token/${id}`)).then(
                (response) => response.text(),
            ),
        );
    }

    function executionCreated(event) {
        ExecutionRestService.getExecution(event.artifact_id).then((execution) => {
            ExecutionService.addTestExecution(execution);
        });
    }

    function executionUpdated(event) {
        ExecutionRestService.getExecution(event.artifact_id).then((execution) => {
            ExecutionService.updateTestExecutionNoBy(execution);
            ExecutionService.updatePresencesOnCampaign();
        });
    }

    function executionDeleted(event) {
        ExecutionRestService.getExecution(event.artifact_id).then((execution) => {
            ExecutionService.removeTestExecution(execution);
            ExecutionService.updatePresencesOnCampaign();
            executionDeletedCallback(execution);
        });
    }

    function artifactLinked(event) {
        ExecutionService.addArtifactLink(event.artifact_id, event.added_artifact_link);
        $rootScope.$applyAsync();
    }

    function campaignUpdated(event) {
        CampaignService.getCampaign(event.artifact_id).then((campaign) => {
            campaignUpdatedCallback(campaign);
        });
    }

    function presenceUpdated(event) {
        if (event.remove_from) {
            ExecutionService.removeViewTestExecution(event.remove_from, event.user);
        }

        ExecutionService.viewTestExecution(event.execution_id, event.user);
        ExecutionService.updatePresencesOnCampaign();
    }

    function errCallback(err) {
        realtime_mercure.abortConnection();
        if (mercureRetryNumber > 1) {
            this.checkDisconnect.disconnect = true;
        }
        if (err instanceof RetriableError || err instanceof FatalError) {
            let timeout = Math.pow(2, mercureRetryNumber) * 1000 + Math.floor(Math.random() * 1000);
            setTimeout(requestJWTToRefreshToken, timeout);
            mercureRetryNumber = mercureRetryNumber + 1;
        }
    }

    function requestJWTToRefreshToken() {
        getToken(campaignId).then((data) => {
            realtime_token = data;
            realtime_mercure.editToken(realtime_token);
        });
    }

    function sucessCallback() {
        if (mercureRetryNumber > 1) {
            this.checkDisconnect.disconnect = true;
        }
        mercureRetryNumber = 0;
    }
}
