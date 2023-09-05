/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { ReportCell } from "@tuleap/plugin-docgen-xlsx";
import { EmptyCell, TextCell } from "@tuleap/plugin-docgen-xlsx";
import type { BacklogItem } from "../../../../type";
import { computeTestStats, getTestStatusFromStats } from "../../../BacklogItems/compute-test-stats";
import { getInternationalizedTestStatus } from "../internationalize-test-status";
import { retrieveArtifacts } from "./Tracker/artifacts-retriever";
import { retrieveTrackers } from "./Tracker/trackers-retriever";
import { transformFieldValueIntoACell } from "./transform-field-value-into-cell";
import type { Artifact } from "./Tracker/artifact";
import type { Tracker } from "./Tracker/tracker";
import type { VueGettextProvider } from "../../../vue-gettext-provider";

const SUPPORTED_EXTRA_FIELD_TYPES: ReadonlySet<string> = new Set([
    "int",
    "float",
    "computed",
    "string",
    "text",
    "date",
    "subon",
    "lud",
]);

export interface RequirementsSection {
    readonly title: TextCell;
    readonly headers: Readonly<{
        0: TextCell;
        1: TextCell;
        2: TextCell;
        3: TextCell;
        [index: number]: TextCell;
    }> &
        TextCell[];
    readonly rows: ReadonlyArray<
        Readonly<{
            0: TextCell;
            1: TextCell;
            2: TextCell;
            3: TextCell;
            [index: number]: ReportCell;
        }> &
            ReportCell[]
    >;
}

interface ExtraFieldsToExtract {
    labels: ReadonlySet<string>;
    banned_fields: ReadonlySet<number>;
}

export async function buildRequirementsSection(
    gettext_provider: VueGettextProvider,
    backlog_items: ReadonlyArray<BacklogItem>,
): Promise<RequirementsSection> {
    const all_full_requirements: ReadonlyMap<number, Artifact> = await retrieveArtifacts(
        backlog_items.map((backlog_item: BacklogItem): number => backlog_item.id),
    );

    const trackers = await retrieveTrackers(
        [...all_full_requirements.values()].map((value) => value.tracker),
    );
    const extra_fields_to_extract = getExtraFieldToExtract(trackers);

    return {
        title: new TextCell(gettext_provider.$gettext("Requirements")),
        headers: [
            new TextCell(gettext_provider.$gettext("Type")),
            new TextCell(gettext_provider.$gettext("ID")),
            new TextCell(gettext_provider.$gettext("Title")),
            new TextCell(gettext_provider.$gettext("Tests status")),
            ...[...extra_fields_to_extract.labels]
                .sort(sortFieldLabel)
                .map((label: string): TextCell => new TextCell(label)),
        ],
        rows: backlog_items.map((backlog_item: BacklogItem) => {
            const requirement = all_full_requirements.get(backlog_item.id);

            let extra_cells: ReportCell[] = [];
            if (requirement) {
                extra_cells = sortExtraCells(
                    getExtraCells(gettext_provider, requirement, extra_fields_to_extract),
                );
            }

            return [
                new TextCell(backlog_item.short_type),
                new TextCell(String(backlog_item.id)),
                new TextCell(backlog_item.label),
                getTestStatusCell(gettext_provider, backlog_item),
                ...extra_cells,
            ];
        }),
    };
}

function getTestStatusCell(
    gettext_provider: VueGettextProvider,
    backlog_item: BacklogItem,
): TextCell {
    return new TextCell(
        getInternationalizedTestStatus(
            gettext_provider,
            getTestStatusFromStats(computeTestStats(backlog_item)),
        ),
    );
}

function sortFieldLabel(label_a: string, label_b: string): number {
    return label_a.localeCompare(label_b);
}

function getExtraFieldToExtract(trackers: ReadonlyArray<Tracker>): ExtraFieldsToExtract {
    const labels: Set<string> = new Set();
    const banned_fields: Set<number> = new Set();

    for (const tracker of trackers) {
        if (tracker.semantics.title) {
            banned_fields.add(tracker.semantics.title.field_id);
        }

        for (const field of tracker.fields) {
            if (SUPPORTED_EXTRA_FIELD_TYPES.has(field.type) && !banned_fields.has(field.field_id)) {
                labels.add(field.label);
            }
        }
    }

    return { labels, banned_fields };
}

function getExtraCells(
    gettext_provider: VueGettextProvider,
    requirement: Artifact,
    extra_fields: ExtraFieldsToExtract,
): Map<string, ReportCell> {
    const extra_cells: Map<string, ReportCell> = new Map();

    for (const field_value of requirement.values) {
        if (
            !SUPPORTED_EXTRA_FIELD_TYPES.has(field_value.type) ||
            !extra_fields.labels.has(field_value.label) ||
            extra_fields.banned_fields.has(field_value.field_id)
        ) {
            continue;
        }

        const already_existing_cell_with_label = extra_cells.get(field_value.label);
        if (already_existing_cell_with_label) {
            extra_cells.set(
                field_value.label,
                already_existing_cell_with_label.withComment(
                    gettext_provider.$gettext(
                        "This requirement have multiple fields with this label, only one value is visible",
                    ),
                ),
            );
            continue;
        }

        const cell = transformFieldValueIntoACell(field_value);
        if (cell !== null) {
            extra_cells.set(field_value.label, cell);
        }
    }

    const extra_field_labels_of_artifact = new Set(extra_cells.keys());
    const extra_field_labels_not_existing_for_the_artifact = [...extra_fields.labels].filter(
        (label: string) => !extra_field_labels_of_artifact.has(label),
    );
    for (const missing_extra_field_label of extra_field_labels_not_existing_for_the_artifact) {
        extra_cells.set(missing_extra_field_label, new EmptyCell());
    }

    return extra_cells;
}

function sortExtraCells(unsorted_cells: ReadonlyMap<string, ReportCell>): ReportCell[] {
    const extra_cells: [string, ReportCell][] = [];
    for (const [label, cell] of unsorted_cells) {
        extra_cells.push([label, cell]);
    }

    return extra_cells
        .sort((a: [string, ReportCell], b: [string, ReportCell]): number =>
            sortFieldLabel(a[0], b[0]),
        )
        .map((label_with_cell: [string, ReportCell]): ReportCell => label_with_cell[1]);
}
