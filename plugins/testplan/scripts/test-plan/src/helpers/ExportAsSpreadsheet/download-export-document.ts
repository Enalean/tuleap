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

import type { ExportReport } from "./Report/report-creator";
import { createExportReport } from "./Report/report-creator";
import type { BacklogItem, Campaign } from "../../type";
import type { VueGettextProvider } from "../vue-gettext-provider";

export async function downloadExportDocument(
    gettext_provider: VueGettextProvider,
    download_document: (
        gettext_provider: VueGettextProvider,
        milestone_title: string,
        report: ExportReport,
    ) => void,
    project_name: string,
    milestone_title: string,
    user_display_name: string,
    backlog_items: ReadonlyArray<BacklogItem>,
    campaigns: ReadonlyArray<Campaign>,
): Promise<void> {
    const current_date = new Date();

    const report = await createExportReport(
        gettext_provider,
        project_name,
        milestone_title,
        user_display_name,
        current_date,
        backlog_items,
        campaigns,
    );
    download_document(gettext_provider, milestone_title, report);
}
