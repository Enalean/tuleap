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

import type {
    ExportDocument,
    Campaign,
    DateTimeLocaleInformation,
    ArtifactFieldValueStepDefinitionEnhancedWithResults,
} from "../../../type";
import { getTraceabilityMatrix } from "./traceability-matrix-creator";
import { getExecutionsForCampaigns } from "./executions-for-campaigns-retriever";
import type { GettextProvider } from "@tuleap/gettext";
import { sprintf } from "sprintf-js";

export async function createExportReport(
    gettext_provider: GettextProvider,
    campaign: Campaign,
    datetime_locale_information: DateTimeLocaleInformation
): Promise<ExportDocument<ArtifactFieldValueStepDefinitionEnhancedWithResults>> {
    const executions_map = await getExecutionsForCampaigns([campaign]);

    return {
        name: sprintf(gettext_provider.gettext("Test campaign %(name)s"), { name: campaign.label }),
        traceability_matrix: getTraceabilityMatrix(executions_map, datetime_locale_information),
        backlog: [],
        tests: [],
    };
}
