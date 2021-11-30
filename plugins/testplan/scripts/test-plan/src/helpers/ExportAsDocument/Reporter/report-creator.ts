/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type { VueGettextProvider } from "../../vue-gettext-provider";
import type { BacklogItem, ExportDocument, GlobalExportProperties } from "../../../type";

export function createExportReport(
    gettext_provider: VueGettextProvider,
    global_properties: GlobalExportProperties,
    backlog_items: ReadonlyArray<BacklogItem>
): ExportDocument {
    return {
        name: gettext_provider.$gettextInterpolate(
            gettext_provider.$gettext("Test Report %{ milestone_title }"),
            { milestone_title: global_properties.milestone_name }
        ),
        backlog: backlog_items,
    };
}
