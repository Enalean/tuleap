/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import { okAsync, type ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import * as fetch_result from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import type { ChangesetValue } from "@tuleap/plugin-tracker-rest-api-types";
import { CurrentArtifactIdentifier } from "@tuleap/plugin-tracker-artifact-common";
import { InitializationAPIClient } from "./InitializationAPIClient";
import type {
    ArtifactResponseFromREST,
    ReducedTrackerRepresentation,
} from "./InitializationAPIClient";
import type { CurrentArtifactWithTrackerStructure } from "../../../domain/initialization/CurrentArtifactWithTrackerStructure";

describe(`InitializationAPIClient`, () => {
    const getClient = (): ReturnType<typeof InitializationAPIClient> => InitializationAPIClient();

    describe(`getCurrentArtifactWithTrackerStructure()`, () => {
        const ARTIFACT_ID = 40;

        const getArtifact = (): ResultAsync<CurrentArtifactWithTrackerStructure, Fault> => {
            return getClient().getCurrentArtifactWithTrackerStructure(
                CurrentArtifactIdentifier.fromId(ARTIFACT_ID),
            );
        };

        it(`will return the current artifact with its tracker structure matching the given artifact id`, async () => {
            const project = { id: 116, label: "hyalinize Maybird", uri: "/projects/116", icon: "" };
            const tracker: ReducedTrackerRepresentation = {
                id: 263,
                item_name: "calciphilous",
                color_name: "lilac-purple",
                project: { id: project.id },
                parent: {
                    id: 72,
                    label: "unridableness",
                    color: "daphne-blue",
                    project,
                    uri: "/trackers/72",
                },
                fields: [
                    {
                        field_id: 866,
                        type: "string",
                        name: "unpredisposed",
                        label: "Interseamed",
                        required: false,
                    },
                    {
                        field_id: 468,
                        type: "string",
                        name: "coracler",
                        label: "Unwittily",
                        required: false,
                    },
                ],
                notifications: { enabled: true },
                workflow: {
                    field_id: 0,
                    is_used: "",
                    is_advanced: true,
                    is_legacy: false,
                    rules: { dates: [], lists: [] },
                    transitions: [],
                },
            };
            const values: ReadonlyArray<ChangesetValue> = [
                { field_id: 866, type: "string", label: "Interseamed", value: "ectogenous" },
                { field_id: 468, type: "string", label: "Unwittily", value: "caesaropapism" },
            ];
            const artifact: ArtifactResponseFromREST = {
                id: ARTIFACT_ID,
                title: "coincoin",
                values,
                tracker,
            };
            const getResponse = jest.spyOn(fetch_result, "getResponse");
            getResponse.mockReturnValue(
                okAsync({
                    headers: new Headers({ Etag: "1607498311", "Last-Modified": "1607498311" }),
                    json: () => Promise.resolve(artifact),
                } as Response),
            );

            const result = await getArtifact();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            const mapped_artifact = result.value;
            expect(mapped_artifact.etag).toBe("1607498311");
            expect(mapped_artifact.last_modified).toBe("1607498311");
            expect(mapped_artifact.id).toBe(ARTIFACT_ID);
            expect(mapped_artifact.title).toBe(artifact.title);
            const mapped_tracker = mapped_artifact.tracker;
            expect(mapped_tracker.item_name).toBe(tracker.item_name);
            expect(mapped_tracker.color_name).toBe(tracker.color_name);
            expect(mapped_tracker.project.id).toBe(tracker.project.id);
            expect(mapped_tracker.parent).toBe(tracker.parent);
            expect(mapped_tracker.are_mentions_effective).toBe(tracker.notifications.enabled);
            expect(mapped_tracker.fields).toBe(tracker.fields);
            expect(mapped_tracker.workflow).toBe(tracker.workflow);
            expect(getResponse.mock.calls[0][0]).toStrictEqual(
                uri`/api/v1/artifacts/${ARTIFACT_ID}?tracker_structure_format=complete`,
            );
        });
    });
});
