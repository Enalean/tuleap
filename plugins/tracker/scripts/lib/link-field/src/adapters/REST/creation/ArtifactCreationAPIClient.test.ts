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

import { describe, expect, it, vi } from "vitest";
import * as fetch_result from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import type { ResultAsync } from "neverthrow";
import { okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { ProjectResponse } from "@tuleap/core-rest-api-types";
import type { ColorName } from "@tuleap/plugin-tracker-constants";
import type { TrackerResponseWithCannotCreateReason } from "@tuleap/plugin-tracker-rest-api-types";
import type { Project } from "../../../domain/Project";
import { ArtifactCreationAPIClient } from "./ArtifactCreationAPIClient";
import type { Tracker } from "../../../domain/Tracker";
import { ProjectIdentifierStub } from "../../../../tests/stubs/ProjectIdentifierStub";
import type { TrackerWithTitleSemantic } from "./TrackerWithTitleSemantic";
import type { ArtifactCreatedIdentifier } from "../../../domain/creation/ArtifactCreatedIdentifier";
import { TrackerIdentifier } from "../../../domain/TrackerIdentifier";
import { TitleFieldIdentifier } from "../../../domain/creation/TitleFieldIdentifier";

describe(`ArtifactCreationAPIClient`, () => {
    const getClient = (): ArtifactCreationAPIClient => {
        return ArtifactCreationAPIClient();
    };

    describe(`getProjects()`, () => {
        const FIRST_PROJECT_ID = 113,
            SECOND_PROJECT_ID = 161,
            FIRST_PROJECT_LABEL = "🐷 Guinea Pig",
            SECOND_PROJECT_LABEL = "Hidden Street";

        const getProjects = (): ResultAsync<readonly Project[], Fault> => {
            return getClient().getProjects();
        };

        it(`will return an array of Projects`, async () => {
            const first_project = {
                id: FIRST_PROJECT_ID,
                label: FIRST_PROJECT_LABEL,
            } as ProjectResponse;
            const second_project = {
                id: SECOND_PROJECT_ID,
                label: SECOND_PROJECT_LABEL,
            } as ProjectResponse;
            const getAllJSON = vi
                .spyOn(fetch_result, "getAllJSON")
                .mockReturnValue(okAsync([first_project, second_project]));

            const result = await getProjects();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toHaveLength(2);
            const [first_returned_project, second_returned_project] = result.value;
            expect(first_returned_project.id).toBe(FIRST_PROJECT_ID);
            expect(first_returned_project.label).toBe(FIRST_PROJECT_LABEL);
            expect(second_returned_project.id).toBe(SECOND_PROJECT_ID);
            expect(second_returned_project.label).toBe(SECOND_PROJECT_LABEL);
            expect(getAllJSON).toHaveBeenCalledWith(uri`/api/projects`, { params: { limit: 50 } });
        });
    });

    describe(`getTrackersByProject()`, () => {
        const PROJECT_ID = 113,
            FIRST_TRACKER_ID = 200,
            SECOND_TRACKER_ID = 161,
            FIRST_TRACKER_LABEL = "🐷 Guinea Pig",
            SECOND_TRACKER_LABEL = "Hidden Street",
            FIRST_TRACKER_COLOR: ColorName = "red-wine",
            SECOND_TRACKER_COLOR: ColorName = "deep-blue";

        const getTrackers = (): ResultAsync<readonly Tracker[], Fault> => {
            return getClient().getTrackersByProject(ProjectIdentifierStub.withId(PROJECT_ID));
        };

        it(`will return an array of Trackers`, async () => {
            const first_tracker = {
                id: FIRST_TRACKER_ID,
                label: FIRST_TRACKER_LABEL,
                color_name: FIRST_TRACKER_COLOR,
                cannot_create_reasons: [],
            } as TrackerResponseWithCannotCreateReason;
            const second_tracker = {
                id: SECOND_TRACKER_ID,
                label: SECOND_TRACKER_LABEL,
                color_name: SECOND_TRACKER_COLOR,
                cannot_create_reasons: [],
            } as TrackerResponseWithCannotCreateReason;
            const getAllJSON = vi
                .spyOn(fetch_result, "getAllJSON")
                .mockReturnValue(okAsync([first_tracker, second_tracker]));

            const result = await getTrackers();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toHaveLength(2);
            const [first_returned_project, second_returned_project] = result.value;
            expect(first_returned_project.label).toBe(FIRST_TRACKER_LABEL);
            expect(first_returned_project.color_name).toBe(FIRST_TRACKER_COLOR);
            expect(second_returned_project.label).toBe(SECOND_TRACKER_LABEL);
            expect(second_returned_project.color_name).toBe(SECOND_TRACKER_COLOR);
            expect(getAllJSON).toHaveBeenCalledWith(uri`/api/projects/${PROJECT_ID}/trackers`, {
                params: {
                    limit: 50,
                    representation: "minimal",
                    with_creation_semantic_check: "title",
                },
            });
        });
    });

    describe(`getTrackerWithTitle()`, () => {
        const TRACKER_ID = 32;
        const getTracker = (): ResultAsync<TrackerWithTitleSemantic, Fault> => {
            return getClient().getTrackerWithTitleSemantic(TrackerIdentifier.fromId(TRACKER_ID));
        };

        it(`will return a Tracker with data about its Title field id`, async () => {
            const tracker: TrackerWithTitleSemantic = {
                id: TRACKER_ID,
                semantics: { title: { field_id: 631 } },
            };
            const getJSON = vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(tracker));

            const result = await getTracker();

            expect(result.unwrapOr(null)).toBe(tracker);
            expect(getJSON.mock.calls[0][0]).toStrictEqual(uri`/api/trackers/${TRACKER_ID}`);
        });
    });

    describe(`createArtifactWithTitle()`, () => {
        const ARTIFACT_ID = 184,
            TRACKER_ID = 334,
            TITLE_FIELD_ID = 770,
            TITLE = "Encumberingly ochletic";
        const createArtifact = (): ResultAsync<ArtifactCreatedIdentifier, Fault> => {
            return getClient().createArtifactWithTitle(
                TrackerIdentifier.fromId(TRACKER_ID),
                TitleFieldIdentifier.fromId(TITLE_FIELD_ID),
                TITLE,
            );
        };

        it(`will create the artifact with given title, and will return the created artifact's identifier`, async () => {
            const postJSON = vi
                .spyOn(fetch_result, "postJSON")
                .mockReturnValue(okAsync({ id: ARTIFACT_ID }));

            const result = await createArtifact();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value.id).toBe(ARTIFACT_ID);
            expect(postJSON).toHaveBeenCalledWith(uri`/api/v1/artifacts`, {
                tracker: { id: TRACKER_ID },
                values: [{ field_id: TITLE_FIELD_ID, value: TITLE }],
            });
        });
    });
});
