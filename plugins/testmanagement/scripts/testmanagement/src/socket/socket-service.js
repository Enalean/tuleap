/*
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

import moment from "moment";

export default SocketService;

SocketService.$inject = [
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

function SocketService(
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
    var self = this;

    Object.assign(self, {
        checkDisconnect: {
            disconnect: false,
        },
        listenTokenExpired,
        listenNodeJSServer,
        listenToExecutionViewed,
        listenToExecutionLeft,
        listenToExecutionCreated,
        listenToExecutionUpdated,
        listenToExecutionDeleted,
        listenToCampaignUpdated,
        listenToArtifactLinked,
        refreshToken,
    });

    function listenTokenExpired() {
        var expired_date = moment(locker.get("token-expired-date")).subtract(5, "m");
        var timeout = expired_date.diff(moment());
        if (timeout < 0) {
            requestJWTToRefreshToken();
        } else {
            $timeout(() => {
                requestJWTToRefreshToken();
            }, timeout);
        }
    }

    function listenNodeJSServer() {
        listenToDisconnect();
        listenToError();
        listenPresences();
        self.listenToArtifactLinked();
        return JWTService.getJWT().then((data) => {
            locker.put("token", data.token);
            locker.put("token-expired-date", JWTService.getTokenExpiredDate(data.token));
            return subscribe();
        });
    }

    function subscribe() {
        SocketFactory.emit("subscription", {
            nodejs_server_version: SharedPropertiesService.getNodeServerVersion(),
            token: locker.get("token"),
            room_id: "testmanagement_" + SharedPropertiesService.getCampaignId(),
            uuid: SharedPropertiesService.getUUID(),
        });
    }

    function refreshToken() {
        SocketFactory.emit("token", {
            token: locker.get("token"),
        });
    }

    function listenToDisconnect() {
        SocketFactory.on("disconnect", () => {
            self.checkDisconnect.disconnect = true;
        });
    }

    function listenToError() {
        SocketFactory.on("error-jwt", (error) => {
            if (error === "JWTExpired") {
                JWTService.getJWT().then((data) => {
                    locker.put("token", data.token);
                    subscribe();
                    ExecutionService.initialization();
                    $rootScope.$broadcast("controller-reload");
                });
            }
        });
    }

    function listenPresences() {
        SocketFactory.on("presences", (presences) => {
            ExecutionService.presences_loaded = true;
            ExecutionService.presences_by_execution = presences;
            ExecutionService.displayPresencesForAllExecutions();
        });
    }

    function listenToExecutionViewed() {
        SocketFactory.on(
            "testmanagement_user:presence",
            ({
                execution_to_remove,
                execution_presences_to_remove,
                execution_to_add,
                execution_presences_to_add,
            }) => {
                if (execution_to_remove) {
                    ExecutionService.displayPresencesByExecution(
                        execution_to_remove,
                        execution_presences_to_remove,
                    );
                }
                if (execution_to_add) {
                    ExecutionService.displayPresencesByExecution(
                        execution_to_add,
                        execution_presences_to_add,
                    );
                    ExecutionService.updatePresencesOnCampaign();
                }
            },
        );
    }

    function listenToExecutionLeft() {
        SocketFactory.on("user:leave", function (uuid) {
            ExecutionService.removeViewTestExecutionByUUID(uuid);
        });
    }

    function listenToExecutionCreated() {
        SocketFactory.on("testmanagement_execution:create", ({ artifact_id }) => {
            ExecutionRestService.getExecution(artifact_id).then((execution) => {
                ExecutionService.addTestExecutionWithoutUpdateCampaignStatus(execution);
            });
        });
    }

    function listenToExecutionUpdated() {
        SocketFactory.on("testmanagement_execution:update", ({ artifact_id, user }) => {
            ExecutionRestService.getExecution(artifact_id).then((execution) => {
                ExecutionService.updateTestExecution(execution, user);
                ExecutionService.updatePresencesOnCampaign();
            });
        });
    }

    function listenToExecutionDeleted(callback) {
        SocketFactory.on("testmanagement_execution:delete", ({ artifact_id }) => {
            ExecutionRestService.getExecution(artifact_id).then((execution) => {
                ExecutionService.removeTestExecutionWithoutUpdateCampaignStatus(execution);
                callback(execution);
            });
        });
    }

    function listenToCampaignUpdated(callback) {
        SocketFactory.on("testmanagement_campaign:update", ({ artifact_id }) => {
            CampaignService.getCampaign(artifact_id).then((campaign) => {
                callback(campaign);
            });
        });
    }

    function listenToArtifactLinked() {
        SocketFactory.on(
            "testmanagement_execution:link_artifact",
            ({ artifact_id, added_artifact_link }) => {
                ExecutionService.addArtifactLink(artifact_id, added_artifact_link);
            },
        );
    }

    function requestJWTToRefreshToken() {
        JWTService.getJWT().then((data) => {
            locker.put("token", data.token);
            locker.put("token-expired-date", JWTService.getTokenExpiredDate(data.token));
            refreshToken();
            listenTokenExpired();
        });
    }
}
