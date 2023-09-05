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

import { buildGeneralSection } from "./Section/general-information-builder";
import type { ReportCell, TextCell } from "@tuleap/plugin-docgen-xlsx";
import { buildRequirementsSection } from "./Section/requirements-builder";
import type { BacklogItem, Campaign } from "../../../type";
import { buildTestResultsSection } from "./Section/test-results-builder";
import { getPlannedTestCasesAssociatedWithCampaignAndTestExec } from "./get-planned-test-cases";
import { buildJustificationsSection } from "./Section/justifications-builder";
import type { VueGettextProvider } from "../../vue-gettext-provider";

export interface ReportSection {
    readonly title?: TextCell;
    readonly headers?: ReadonlyArray<TextCell>;
    readonly rows: ReadonlyArray<ReadonlyArray<ReportCell>>;
}

export interface ExportReport {
    readonly sections: ReadonlyArray<ReportSection>;
}

export async function createExportReport(
    gettext_provider: VueGettextProvider,
    project_name: string,
    milestone_title: string,
    user_display_name: string,
    current_date: Date,
    backlog_items: ReadonlyArray<BacklogItem>,
    campaigns: ReadonlyArray<Campaign>,
): Promise<ExportReport> {
    const planned_test_cases = getPlannedTestCasesAssociatedWithCampaignAndTestExec(
        gettext_provider,
        backlog_items,
        campaigns,
    );

    const requirements_section = buildRequirementsSection(gettext_provider, backlog_items);
    const justifications_section = buildJustificationsSection(gettext_provider, planned_test_cases);

    return {
        sections: [
            buildGeneralSection(
                gettext_provider,
                project_name,
                milestone_title,
                user_display_name,
                current_date,
            ),
            await requirements_section,
            buildTestResultsSection(gettext_provider, planned_test_cases),
            await justifications_section,
        ],
    };
}
