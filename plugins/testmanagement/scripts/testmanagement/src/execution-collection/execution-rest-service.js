/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

export default ExecutionRestService;
import { get, patch, post, put } from "@tuleap/tlp-fetch";

ExecutionRestService.$inject = ["$http", "$q", "SharedPropertiesService"];

function ExecutionRestService($http, $q, SharedPropertiesService) {
    const headers = {
        "content-type": "application/json",
        "X-Client-UUID": SharedPropertiesService.getUUID(),
    };

    const self = this;
    Object.assign(self, {
        getRemoteExecutions,
        postTestExecution,
        putTestExecution,
        changePresenceOnTestExecution,
        leaveTestExecution,
        getArtifactById,
        linkIssue,
        linkIssueWithoutComment,
        getLinkedArtifacts,
        getExecution,
        updateExecutionToUseLatestVersionOfDefinition,
        updateStepStatus,
        createFileInTestExecution,
    });

    function getRemoteExecutions(campaign_id, limit, offset) {
        return $q.when(
            get(
                encodeURI(
                    `/api/v1/testmanagement_campaigns/${campaign_id}/testmanagement_executions`,
                ),
                {
                    params: { limit, offset },
                },
            ).then((response) => {
                const total = response.headers.get("X-PAGINATION-SIZE");
                return response.json().then((executions) => {
                    return { results: executions, total };
                });
            }),
        );
    }

    function postTestExecution(tracker_id, definition_id, status) {
        return $q.when(
            post(encodeURI("/api/v1/testmanagement_executions"), {
                headers,
                body: JSON.stringify({
                    tracker: { id: tracker_id },
                    definition_id,
                    status,
                }),
            }).then((response) => response.json()),
        );
    }

    function putTestExecution(
        execution_id,
        new_status,
        results,
        uploaded_file_ids,
        deleted_file_ids,
    ) {
        let param = {
            status: new_status,
            uploaded_file_ids: uploaded_file_ids,
            deleted_file_ids: deleted_file_ids,
            results: results,
        };

        const body = JSON.stringify(param);

        return $q.when(
            put(encodeURI(`/api/v1/testmanagement_executions/${execution_id}`), {
                headers,
                body,
            })
                .then((response) => response.json())
                .catch((exception) => {
                    if (Object.prototype.hasOwnProperty.call(exception, "response")) {
                        return exception.response.json().then((json) => $q.reject(json.error));
                    }

                    return $q.reject(exception);
                }),
        );
    }

    function updateExecutionToUseLatestVersionOfDefinition(execution_id) {
        return $q.when(
            patch(encodeURI(`/api/v1/testmanagement_executions/${execution_id}`), {
                headers,
                body: JSON.stringify({ force_use_latest_definition_version: true }),
            }),
        );
    }

    function changePresenceOnTestExecution(execution_id, old_execution_id) {
        return $q.when(
            patch(encodeURI(`/api/v1/testmanagement_executions/${execution_id}/presences`), {
                headers,
                body: JSON.stringify({
                    uuid: SharedPropertiesService.getUUID(),
                    remove_from: old_execution_id,
                }),
            }),
        );
    }

    function leaveTestExecution(execution_id) {
        return changePresenceOnTestExecution(execution_id, execution_id);
    }

    function getArtifactById(artifact_id) {
        return $q.when(
            get(encodeURI(`/api/v1/artifacts/${artifact_id}`)).then((response) => response.json()),
        );
    }

    function linkIssue(issue_id, test_execution) {
        var comment = {
            body:
                "<p>" +
                test_execution.previous_result.result +
                "</p>" +
                " <em>" +
                test_execution.definition.summary +
                "</em><br/>" +
                "<blockquote>" +
                test_execution.definition.description +
                "</blockquote>",
            format: "html",
        };

        return linkExecutionToIssue(issue_id, test_execution, comment);
    }

    function linkIssueWithoutComment(issue_id, test_execution) {
        var comment = {
            body: "",
            format: "text",
        };

        return linkExecutionToIssue(issue_id, test_execution, comment);
    }

    function linkExecutionToIssue(issue_id, test_execution, comment) {
        return $q.when(
            patch(encodeURI(`/api/v1/testmanagement_executions/${test_execution.id}/issues`), {
                headers,
                body: JSON.stringify({ issue_id, comment }),
            }).catch((exception) => {
                return exception.response.json().then((json) => $q.reject(json.error));
            }),
        );
    }

    function getLinkedArtifacts(test_execution, limit, offset) {
        const { id: execution_id } = test_execution;
        return $q.when(
            get(encodeURI(`/api/v1/artifacts/${execution_id}/linked_artifacts`), {
                params: {
                    direction: "forward",
                    nature: "",
                    limit,
                    offset,
                },
            }).then((response) => {
                const total = Number.parseInt(response.headers.get("X-PAGINATION-SIZE"), 10);
                return response.json().then(({ collection }) => {
                    return { collection, total };
                });
            }),
        );
    }

    function getExecution(execution_id) {
        return $q.when(
            get(encodeURI(`/api/v1/testmanagement_executions/${execution_id}`)).then((response) =>
                response.json(),
            ),
        );
    }

    function updateStepStatus(test_execution, step_id, step_status) {
        const { id: execution_id } = test_execution;
        return $q.when(
            patch(encodeURI(`/api/v1/testmanagement_executions/${execution_id}`), {
                headers,
                body: JSON.stringify({ steps_results: [{ step_id, status: step_status }] }),
            }).catch((exception) => {
                return exception.response.json().then((json) => $q.reject(json.error));
            }),
        );
    }

    function createFileInTestExecution(execution, { name, file_size, file_type }) {
        return $q.when(
            post(execution.upload_url, {
                headers,
                body: JSON.stringify({
                    name,
                    file_size,
                    file_type,
                }),
            })
                .then((response) => response.json())
                .catch((exception) => {
                    return exception.response.json().then((json) => $q.reject(json.error));
                }),
        );
    }
}
