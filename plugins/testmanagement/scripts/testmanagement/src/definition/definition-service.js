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

import _ from "lodash";
import { recursiveGet } from "tlp";
import { UNCATEGORIZED } from "./definition-constants.js";

import { getDefinitions as tlpGetDefinitions } from "../api/rest-querier.js";

export default DefinitionService;

DefinitionService.$inject = ["Restangular", "$q", "SharedPropertiesService"];

function DefinitionService(Restangular, $q, SharedPropertiesService) {
    var rest = Restangular.withConfig(function (RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl("/api/v1");
    });

    return {
        UNCATEGORIZED,
        getDefinitions,
        getDefinitionReports,
        getArtifactById,
        getDefinitionById,
        getTracker,
    };

    function getDefinitions(project_id, report_id) {
        return tlpGetDefinitions(project_id, report_id).then((definitions) =>
            categorize(definitions)
        );
    }

    function categorize(definitions) {
        return definitions.map((definition) => {
            return _.merge(definition, {
                category: definition.category || UNCATEGORIZED,
            });
        });
    }

    async function getDefinitionReports() {
        var def_tracker_id = SharedPropertiesService.getDefinitionTrackerId();
        const response = await recursiveGet(
            "/api/v1/trackers/" + encodeURI(def_tracker_id) + "/tracker_reports",
            {
                params: {
                    limit: 10,
                },
            }
        );

        return response;
    }

    function getArtifactById(artifact_id) {
        return rest
            .one("artifacts", artifact_id)
            .get()
            .then(function (response) {
                return response.data;
            });
    }

    function getDefinitionById(artifact_id) {
        return rest
            .one("testmanagement_definitions", artifact_id)
            .get()
            .then(function (response) {
                return response.data;
            });
    }

    function getTracker(tracker_id) {
        return rest
            .one("trackers", tracker_id)
            .get()
            .then(function (response) {
                return response.data;
            });
    }
}
