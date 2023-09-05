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

import { DateCell, TextCell } from "@tuleap/plugin-docgen-xlsx";
import type { VueGettextProvider } from "../../../vue-gettext-provider";

export interface GeneralSection {
    readonly rows: readonly [
        readonly [TextCell, TextCell],
        readonly [TextCell, TextCell],
        readonly [TextCell, DateCell],
        readonly [TextCell, TextCell],
    ];
}

export function buildGeneralSection(
    gettext_provider: VueGettextProvider,
    project_name: string,
    milestone_title: string,
    user_display_name: string,
    current_date: Date,
): GeneralSection {
    return {
        rows: [
            [new TextCell(gettext_provider.$gettext("Project")), new TextCell(project_name)],
            [new TextCell(gettext_provider.$gettext("Milestone")), new TextCell(milestone_title)],
            [
                new TextCell(gettext_provider.$gettext("Report extracted on")),
                new DateCell(current_date),
            ],
            [
                new TextCell(gettext_provider.$gettext("Report extracted by")),
                new TextCell(user_display_name),
            ],
        ],
    };
}
