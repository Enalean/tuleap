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

import { TextCell } from "./report-cells";
import { BacklogItem } from "../../type";
import { computeTestStats, getTestStatusFromStats } from "../BacklogItems/compute-test-stats";
import { getInternationalizedTestStatus } from "./internationalize-test-status";

export interface RequirementsSection {
    readonly title: TextCell;
    readonly headers: readonly [TextCell, TextCell, TextCell, TextCell];
    readonly rows: ReadonlyArray<readonly [TextCell, TextCell, TextCell, TextCell]>;
}

export function buildRequirementsSection(
    gettext_provider: VueGettextProvider,
    backlog_items: ReadonlyArray<BacklogItem>
): RequirementsSection {
    return {
        title: new TextCell(gettext_provider.$gettext("Requirements")),
        headers: [
            new TextCell(gettext_provider.$gettext("Type")),
            new TextCell(gettext_provider.$gettext("ID")),
            new TextCell(gettext_provider.$gettext("Title")),
            new TextCell(gettext_provider.$gettext("Tests status")),
        ],
        rows: backlog_items.map((backlog_item: BacklogItem) => [
            new TextCell(backlog_item.short_type),
            new TextCell(String(backlog_item.id)),
            new TextCell(backlog_item.label),
            getTestStatusCell(gettext_provider, backlog_item),
        ]),
    };
}

function getTestStatusCell(
    gettext_provider: VueGettextProvider,
    backlog_item: BacklogItem
): TextCell {
    return new TextCell(
        getInternationalizedTestStatus(
            gettext_provider,
            getTestStatusFromStats(computeTestStats(backlog_item))
        )
    );
}
