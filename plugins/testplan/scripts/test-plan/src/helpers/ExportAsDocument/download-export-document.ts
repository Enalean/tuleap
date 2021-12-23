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

import { createExportReport } from "./Reporter/report-creator";
import type { Campaign, ExportDocument, GlobalExportProperties } from "../../type";
import type { VueGettextProvider } from "../vue-gettext-provider";
import type { DateTimeLocaleInformation } from "../../type";
import type { BacklogItem, ArtifactFieldValueStepDefinitionEnhancedWithResults } from "../../type";

export async function downloadExportDocument(
    global_export_properties: GlobalExportProperties,
    gettext_provider: VueGettextProvider,
    download_document: (
        document: ExportDocument<ArtifactFieldValueStepDefinitionEnhancedWithResults>,
        gettext_provider: VueGettextProvider,
        global_export_properties: GlobalExportProperties,
        datetime_locale_information: DateTimeLocaleInformation
    ) => void,
    backlog_items: ReadonlyArray<BacklogItem>,
    campaigns: ReadonlyArray<Campaign>
): Promise<void> {
    const datetime_locale_information: DateTimeLocaleInformation = {
        locale: global_export_properties.user_locale.replace("_", "-"),
        timezone: global_export_properties.user_timezone,
    };

    const report = await createExportReport(
        gettext_provider,
        global_export_properties,
        backlog_items,
        campaigns,
        datetime_locale_information
    );

    download_document(
        report,
        gettext_provider,
        global_export_properties,
        datetime_locale_information
    );
}
