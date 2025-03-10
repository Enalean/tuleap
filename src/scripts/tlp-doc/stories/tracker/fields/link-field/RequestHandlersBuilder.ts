/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import type { HttpHandler } from "msw";
import { http, HttpResponse } from "msw";
import {
    FORWARD_DIRECTION,
    IS_CHILD_LINK_TYPE,
    UNTYPED_LINK,
} from "@tuleap/plugin-tracker-constants";
import type { LinkTypeRepresentation } from "@tuleap/plugin-tracker-rest-api-types";
import { LinkTypeRepresentationBuilder } from "./LinkTypeRepresentationBuilder";
import type { ArtifactResponse } from "./ArtifactRespresentationBuilder";
import { ArtifactRespresentationBuilder } from "./ArtifactRespresentationBuilder";
import { TrackerRepresentationBuilder } from "./TrackerRepresentationBuilder";
import { ProjectRepresentationBuilder } from "./ProjectRepresentationBuilder";
import { ProjectResponseBuilder } from "./ProjectResponseBuilder";
import { SearchResultEntryBuilder } from "./SearchResultEntryBuilder";
import type { SearchResultEntry, UserHistoryEntry } from "@tuleap/core-rest-api-types";

function isGettingUntypedLinks(url: URL): boolean {
    return (
        url.searchParams.get("nature") === UNTYPED_LINK &&
        url.searchParams.get("direction") === FORWARD_DIRECTION
    );
}

function isGettingParentOfLinks(url: URL): boolean {
    return (
        url.searchParams.get("nature") === IS_CHILD_LINK_TYPE &&
        url.searchParams.get("direction") === FORWARD_DIRECTION
    );
}

interface RequestHandlersBuilder {
    build(): HttpHandler[];
}

export const RequestHandlersBuilder = (
    current_project_id: number,
    current_artifact_id: number,
    current_tracker_shortname: string,
): RequestHandlersBuilder => {
    const current_project = ProjectRepresentationBuilder.aProject(current_project_id)
        .withLabel("Hidden Railroad")
        .withIcon("🛤️")
        .build();
    const current_project_response = ProjectResponseBuilder.aProject(current_project_id)
        .withLabel("🛤️ Hidden Railroad")
        .build();
    const other_project = ProjectRepresentationBuilder.aProject(102)
        .withLabel("Pointless Rhinestone")
        .withIcon("🐼")
        .build();

    const requests_tracker = TrackerRepresentationBuilder.aTracker(62)
        .withColor("teddy-brown")
        .inProject(other_project)
        .withShortName("request")
        .build();
    const tasks_tracker = TrackerRepresentationBuilder.aTracker(629)
        .withColor("lake-placid-blue")
        .inProject(current_project)
        .withShortName("task")
        .build();
    const epics_tracker = TrackerRepresentationBuilder.aTracker(489)
        .withColor("deep-blue")
        .inProject(current_project)
        .withShortName("epic")
        .build();

    interface ExistingLinksResponse {
        readonly natures: LinkTypeRepresentation[];
    }

    const existing_link_types_handler = http.get<never, never, ExistingLinksResponse>(
        "/api/v1/artifacts/*/links",
        () =>
            HttpResponse.json({
                natures: [
                    LinkTypeRepresentationBuilder.buildUntypedLinkTo(),
                    LinkTypeRepresentationBuilder.buildParentOf(),
                ],
            }),
    );

    interface LinkedArtifactsResponse {
        readonly collection: ArtifactResponse[];
    }

    const existing_links_handler = http.get<never, never, LinkedArtifactsResponse>(
        "/api/v1/artifacts/*/linked_artifacts",
        ({ request }) => {
            const url = new URL(request.url);
            if (isGettingUntypedLinks(url)) {
                return HttpResponse.json(
                    {
                        collection: [
                            ArtifactRespresentationBuilder.anArtifact(599)
                                .withTitle("Mike Uniform")
                                .withStatus({ value: "Ongoing", color: "acid-green" }, true)
                                .ofTracker(requests_tracker)
                                .build(),
                        ],
                    },
                    { headers: new Headers({ "X-PAGINATION-SIZE": "1" }) },
                );
            }
            if (isGettingParentOfLinks(url)) {
                return HttpResponse.json(
                    {
                        collection: [
                            ArtifactRespresentationBuilder.anArtifact(667)
                                .withTitle("Yankee Lima")
                                .withStatus({ value: "Open", color: null }, true)
                                .ofTracker(tasks_tracker)
                                .build(),
                        ],
                    },
                    { headers: new Headers({ "X-PAGINATION-SIZE": "1" }) },
                );
            }
            return new HttpResponse(null, { status: 404, statusText: "Not Found" });
        },
    );

    const possible_parents_handler = http.get<never, never, ArtifactResponse[]>(
        "/api/v1/trackers/*/parent_artifacts",
        () => {
            return HttpResponse.json(
                [
                    ArtifactRespresentationBuilder.anArtifact(1411)
                        .withTitle("Quebec Victor")
                        .withStatus({ value: "Planned", color: "chrome-silver" }, true)
                        .ofTracker(epics_tracker)
                        .build(),
                    ArtifactRespresentationBuilder.anArtifact(1918)
                        .withTitle("Bravo X-Ray")
                        .withStatus({ value: "Done", color: "inca-silver" }, false)
                        .ofTracker(epics_tracker)
                        .build(),
                ],
                { headers: new Headers({ "X-PAGINATION-SIZE": "2" }) },
            );
        },
    );

    interface GetArtifactByIDPathParams {
        id: string;
    }

    const matching_artifact_by_id_handler = http.get<
        GetArtifactByIDPathParams,
        never,
        ArtifactResponse
    >("/api/v1/artifacts/:id", ({ params }) => {
        const artifact_id = Number.parseInt(params.id, 10);
        if (Number.isNaN(artifact_id)) {
            return new HttpResponse(null, { status: 400, statusText: "Bad Request" });
        }
        return HttpResponse.json(
            ArtifactRespresentationBuilder.anArtifact(artifact_id)
                .withTitle("Searched artifact by ID")
                .withStatus({ value: "Open", color: null }, true)
                .ofTracker(requests_tracker)
                .build(),
        );
    });

    const current_artifact_entry = SearchResultEntryBuilder.anArtifact(current_artifact_id)
        .withTitle("Should not be found when editing existing artifact")
        .withReferenceKey(current_tracker_shortname)
        .withBadges({ label: "Ongoing", color: "daphne-blue" })
        .inProject(current_project_response)
        .build();

    interface FullTextSearchPayload {
        keywords: string;
    }

    const full_text_search_handler = http.post<never, FullTextSearchPayload, SearchResultEntry[]>(
        "/api/search",
        async ({ request }) => {
            const body: FullTextSearchPayload = await request.json();

            return HttpResponse.json([
                SearchResultEntryBuilder.anArtifact(546)
                    .withTitle(body.keywords + " Sierra")
                    .withReferenceKey("request")
                    .withColor("teddy-brown")
                    .withBadges({ label: "To do", color: "firemist-silver" })
                    .inProject(current_project_response)
                    .build(),
                current_artifact_entry,
            ]);
        },
    );

    interface UserHistoryResponse {
        entries: UserHistoryEntry[];
    }

    const history_handler = http.get<never, never, UserHistoryResponse>(
        "/api/v1/users/*/history",
        () => {
            return HttpResponse.json({
                entries: [
                    SearchResultEntryBuilder.anArtifact(914)
                        .withTitle("Bravo Alpha")
                        .withReferenceKey("request")
                        .withColor("teddy-brown")
                        .withBadges({ label: "Done", color: "inca-silver" })
                        .inProject(current_project_response)
                        .build(),
                    current_artifact_entry,
                ],
            });
        },
    );

    return {
        build(): HttpHandler[] {
            return [
                existing_link_types_handler,
                existing_links_handler,
                possible_parents_handler,
                matching_artifact_by_id_handler,
                full_text_search_handler,
                history_handler,
            ];
        },
    };
};
