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

import { get, post, patch } from "@tuleap/tlp-fetch";

export default CampaignService;

CampaignService.$inject = ["$q"];

function CampaignService($q) {
    const headers = { "content-type": "application/json" };

    return {
        getCampaign,
        createCampaign,
        patchCampaign,
        patchExecutions,
        triggerAutomatedTests,
    };

    function getCampaign(campaign_id) {
        return $q.when(
            get(encodeURI(`/api/v1/testmanagement_campaigns/${campaign_id}`)).then((response) =>
                response.json(),
            ),
        );
    }

    function createCampaign(campaign, test_selector, milestone_id, report_id) {
        const milestone_query = milestone_id ? `&milestone_id=${milestone_id}` : "";
        const report_query = report_id ? `&report_id=${report_id}` : "";
        const uri = `/api/v1/testmanagement_campaigns?test_selector=${test_selector}${milestone_query}${report_query}`;
        return $q.when(post(encodeURI(uri), { headers, body: JSON.stringify(campaign) }));
    }

    function patchCampaign(campaign_id, label, job_configuration) {
        const body = JSON.stringify({ label, job_configuration });
        return $q.when(
            patch(encodeURI(`/api/v1/testmanagement_campaigns/${campaign_id}`), {
                headers,
                body,
            }).then((response) => response.json()),
        );
    }

    function patchExecutions(campaign_id, definition_ids, execution_ids) {
        const body = JSON.stringify({
            definition_ids_to_add: definition_ids,
            execution_ids_to_remove: execution_ids,
        });
        return $q
            .when(
                patch(
                    encodeURI(
                        `/api/v1/testmanagement_campaigns/${campaign_id}/testmanagement_executions`,
                    ),
                    { headers, body },
                ),
            )
            .then((response) => {
                const total = response.headers.get("X-PAGINATION-SIZE");
                return response.json().then((executions) => {
                    return { results: executions, total };
                });
            });
    }

    function triggerAutomatedTests(campaign_id) {
        return $q.when(
            post(
                encodeURI(`/api/v1/testmanagement_campaigns/${campaign_id}/automated_tests`),
            ).catch((error) => {
                return error.response.json().then((json) => $q.reject(json.error));
            }),
        );
    }
}
