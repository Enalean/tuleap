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

import type { Result } from "neverthrow";
import { err, ok } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { Option } from "@tuleap/option";
import type {
    DateSelectableRepresentation,
    NumericSelectableRepresentation,
    Selectable,
    SelectableArtifactRepresentation,
    SelectableReportContentRepresentation,
    SelectableRepresentation,
    TextSelectableRepresentation,
} from "./cross-tracker-rest-api-types";
import {
    DATE_SELECTABLE_TYPE,
    NUMERIC_SELECTABLE_TYPE,
    TEXT_SELECTABLE_TYPE,
} from "./cross-tracker-rest-api-types";
import type { ArtifactsTable, Cell } from "../domain/ArtifactsTable";
import { DATE_CELL, NUMERIC_CELL, TEXT_CELL } from "../domain/ArtifactsTable";

export type ArtifactsTableBuilder = {
    mapReportToArtifactsTable(report: SelectableReportContentRepresentation): ArtifactsTable;
};

const isDateSelectableRepresentation = (
    representation: SelectableRepresentation,
): representation is DateSelectableRepresentation => "with_time" in representation;

const isNumericSelectableRepresentation = (
    representation: SelectableRepresentation,
): representation is NumericSelectableRepresentation =>
    representation.value === null || typeof representation.value === "number";

const isTextSelectableRepresentation = (
    representation: SelectableRepresentation,
): representation is TextSelectableRepresentation =>
    representation.value === null || typeof representation.value === "string";

function buildCell(
    selectable: Selectable,
    artifact: SelectableArtifactRepresentation,
): Result<Cell, Fault> {
    const artifact_value = artifact[selectable.name];
    switch (selectable.type) {
        case DATE_SELECTABLE_TYPE:
            if (!isDateSelectableRepresentation(artifact_value)) {
                // This is likely a developer mistake in the backend,
                // we throw so that Tuleap devs can hear about it and fix it
                throw Error(
                    `Expected Artifact value for ${selectable.name} to be a date format, but it was not`,
                );
            }
            return ok({
                type: DATE_CELL,
                value: Option.fromNullable(artifact_value.value),
                with_time: artifact_value.with_time,
            });
        case NUMERIC_SELECTABLE_TYPE:
            if (!isNumericSelectableRepresentation(artifact_value)) {
                // This is likely a developer mistake in the backend,
                // we throw so that Tuleap devs can hear about it and fix it
                throw Error(
                    `Expected Artifact value for ${selectable.name} to be a number format, but it was not`,
                );
            }
            return ok({
                type: NUMERIC_CELL,
                value: Option.fromNullable(artifact_value.value),
            });
        case TEXT_SELECTABLE_TYPE:
            if (!isTextSelectableRepresentation(artifact_value)) {
                // This is likely a developer mistake in the backend,
                // we throw so that Tuleap devs can hear about it and fix it
                throw Error(
                    `Expected Artifact value for ${selectable.name} to be a text format, but it was not`,
                );
            }
            return ok({
                type: TEXT_CELL,
                value: Option.fromNullable(artifact_value.value),
            });
        default:
            return err(Fault.fromMessage(`Selectable type is not supported`));
    }
}

export const ArtifactsTableBuilder = (): ArtifactsTableBuilder => {
    return {
        mapReportToArtifactsTable(report): ArtifactsTable {
            const initial_table: ArtifactsTable = {
                columns: new Set(),
                rows: [],
            };
            return report.artifacts.reduce((accumulator, artifact) => {
                const row = new Map<string, Cell>();
                for (const selectable of report.selected) {
                    // Filter out unsupported selectable
                    buildCell(selectable, artifact).map((cell) => {
                        accumulator.columns.add(selectable.name);
                        row.set(selectable.name, cell);
                    });
                }
                return {
                    columns: accumulator.columns,
                    rows: accumulator.rows.concat([row]),
                };
            }, initial_table);
        },
    };
};
