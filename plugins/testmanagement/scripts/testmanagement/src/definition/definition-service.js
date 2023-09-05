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

import { merge } from "lodash-es";
import { get, recursiveGet } from "@tuleap/tlp-fetch";
import { UNCATEGORIZED } from "./definition-constants.js";

import { getDefinitions as tlpGetDefinitions } from "../api/rest-querier.js";

export default DefinitionService;

DefinitionService.$inject = ["$q", "SharedPropertiesService"];

function DefinitionService($q, SharedPropertiesService) {
    return {
        UNCATEGORIZED,
        getDefinitions,
        getDefinitionReports,
        getArtifactById,
        getDefinitionById,
        getTracker,
    };

    function getDefinitions(project_id, report_id) {
        return $q.when(
            tlpGetDefinitions(project_id, report_id).then((definitions) => categorize(definitions)),
        );
    }

    function categorize(definitions) {
        return definitions.map((definition) => {
            return merge(definition, {
                category: definition.category || UNCATEGORIZED,
            });
        });
    }

    function getDefinitionReports() {
        const def_tracker_id = SharedPropertiesService.getDefinitionTrackerId();
        return $q.when(
            recursiveGet(encodeURI(`/api/v1/trackers/${def_tracker_id}/tracker_reports`), {
                params: { limit: 10 },
            }),
        );
    }

    function getArtifactById(artifact_id) {
        return $q.when(
            get(encodeURI(`/api/v1/artifacts/${artifact_id}`)).then((response) => response.json()),
        );
    }

    function getDefinitionById(artifact_id) {
        return $q.when(
            get(encodeURI(`/api/v1/testmanagement_definitions/${artifact_id}`)).then((response) =>
                response.json(),
            ),
        );
    }

    function getTracker(tracker_id) {
        return $q.when(
            get(encodeURI(`/api/v1/trackers/${tracker_id}`)).then((response) => response.json()),
        );
    }
}
