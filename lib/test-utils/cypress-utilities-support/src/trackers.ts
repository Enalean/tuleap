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
} from "@tuleap/plugin-tracker-rest-api-types";

interface Tracker {
    readonly id: number;
    readonly item_name: string;
}

Cypress.Commands.add(
    "getTrackerIdFromREST",
    (project_id: number, tracker_name: string): Cypress.Chainable<number> => {
        return cy.getFromTuleapAPI(`/api/projects/${project_id}/trackers`).then((response) => {
            return response.body.find((tracker: Tracker) => tracker.item_name === tracker_name).id;
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

Cypress.Commands.add(
    "createArtifact",
    (payload: ArtifactCreationPayload): Cypress.Chainable<number> =>
        cy.getFromTuleapAPI(`/api/trackers/${payload.tracker_id}`).then((response) => {
            const result = response.body;

            const title_id = result.fields.find(
                (field: StructureFields) => field.name === payload.title_field_name,
            ).field_id;
            const artifact_payload = {
                tracker: { id: payload.tracker_id },
                values: [
                    {
                        field_id: title_id,
                        value: payload.artifact_title,
                    },
                    ...getStatusPayload(payload.artifact_status, result.fields),
                ],
            };

            return cy
                .postFromTuleapApi("/api/artifacts/", artifact_payload)
                .then((response) => response.body.id);
        }),
);
