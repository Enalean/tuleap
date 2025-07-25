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

import type {
    ListNewChangesetValue,
    StaticBoundListField,
    StructureFields,
    TrackerResponseNoInstance,
} from "@tuleap/plugin-tracker-rest-api-types";

type Tracker = Pick<TrackerResponseNoInstance, "id" | "item_name" | "fields">;

Cypress.Commands.add(
    "getTrackerIdFromREST",
    (project_id: number, tracker_name: string): Cypress.Chainable<number> => {
        return cy
            .getFromTuleapAPI<Tracker[]>(`/api/projects/${project_id}/trackers`)
            .then((response) => {
                const tracker = response.body.find(
                    (tracker: Tracker) => tracker.item_name === tracker_name,
                );
                if (!tracker) {
                    throw new Error(`Unable to find the id of the tracker named "${tracker_name}"`);
                }

                return tracker.id;
            });
    },
);

const statusFieldGuard = (field: StructureFields): field is StaticBoundListField =>
    field.type === "sb" && field.bindings.type === "static";

function getStatusPayload(
    status_label: string | undefined,
    fields: readonly StructureFields[],
): ListNewChangesetValue[] {
    if (status_label === undefined) {
        return [];
    }
    const status = fields.find((field) => field.name === "status");
    if (!status || !statusFieldGuard(status)) {
        throw Error("No status field in tracker structure");
    }
    const status_id = status.field_id;
    const status_bind_value = status.values.find((value) => value.label === status_label);
    if (status_bind_value === undefined) {
        throw Error(`Could not find status value with given label: ${status_label}`);
    }
    return [
        {
            bind_value_ids: [status_bind_value.id],
            field_id: status_id,
        },
    ];
}

export interface ArtifactCreationPayload {
    tracker_id: number;
    artifact_title: string;
    artifact_status?: string;
    title_field_name: string;
}

interface CreatedArtifactResponse {
    id: number;
}

Cypress.Commands.add(
    "createArtifact",
    (payload: ArtifactCreationPayload): Cypress.Chainable<number> =>
        cy.getFromTuleapAPI<Tracker>(`/api/trackers/${payload.tracker_id}`).then((response) => {
            const result = response.body;

            const title_field = result.fields.find(
                (field: StructureFields) => field.name === payload.title_field_name,
            );

            if (!title_field) {
                throw new Error(`Unable to find a field named ${payload.title_field_name}`);
            }

            const artifact_payload = {
                tracker: { id: payload.tracker_id },
                values: [
                    {
                        field_id: title_field.field_id,
                        value: payload.artifact_title,
                    },
                    ...getStatusPayload(payload.artifact_status, result.fields),
                ],
            };

            return cy
                .postFromTuleapApi<CreatedArtifactResponse>("/api/artifacts/", artifact_payload)
                .then((response) => response.body.id);
        }),
);

type ArtifactFieldToCreate =
    | {
          field_id: number;
          value: string;
      }
    | {
          all_links?: Array<ArtifactLinkField>;
      };

type ArtifactLinkField = {
    id: number;
    direction: "reverse" | "forward";
    type: string;
};

interface TrackerField {
    shortname: string;
    value?: string;
    all_links?: Array<ArtifactLinkField>;
}

export interface ArtifactWithFieldCreationPayload {
    tracker_id: number;
    fields: Array<TrackerField>;
}

Cypress.Commands.add(
    "createArtifactWithFields",
    (payload: ArtifactWithFieldCreationPayload): Cypress.Chainable<number> =>
        cy.getFromTuleapAPI<Tracker>(`/api/trackers/${payload.tracker_id}`).then((response) => {
            const result = response.body;

            const fields_to_create: Array<ArtifactFieldToCreate> = [];
            payload.fields.forEach((field_to_add: TrackerField) => {
                const target_field = result.fields.find(
                    (field: StructureFields) => field.name === field_to_add.shortname,
                );

                if (!target_field) {
                    throw new Error(
                        `Unable to find a field using the shortname ${field_to_add.shortname}`,
                    );
                }
                if (target_field.type === "art_link") {
                    fields_to_create.push({
                        field_id: target_field.field_id,
                        all_links: field_to_add.all_links,
                    });
                }
                if (field_to_add.value) {
                    fields_to_create.push({
                        field_id: target_field.field_id,
                        value: field_to_add.value,
                    });
                }
            });

            const artifact_payload = {
                tracker: { id: payload.tracker_id },
                values: fields_to_create,
            };

            return cy
                .postFromTuleapApi<CreatedArtifactResponse>("/api/artifacts/", artifact_payload)
                .then((response) => response.body.id);
        }),
);
