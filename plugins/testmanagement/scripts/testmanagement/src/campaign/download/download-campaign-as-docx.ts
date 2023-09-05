/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { Campaign } from "../../type";
import type { ArtifactLinkType } from "@tuleap/plugin-docgen-docx/src";

export async function downloadCampaignAsDocx(
    campaign: Campaign,
    platform_name: string,
    platform_logo_url: string,
    project_name: string,
    user_display_name: string,
    user_timezone: string,
    user_locale: string,
    base_url: string,
    project_id: number,
    testdefinition_tracker_id: number | null,
    artifact_links_types: ReadonlyArray<ArtifactLinkType>,
): Promise<void> {
    const { downloadExportDocument } = await import(
        /* webpackChunkName: "testmanagement-download-export-doc" */ "../../helpers/ExportAsDocument/download-export-document"
    );

    const { downloadDocx } = await import(
        /* webpackChunkName: "testmanagement-download-docx-export-doc" */ "../../helpers/ExportAsDocument/Exporter/DOCX/download-docx"
    );

    await downloadExportDocument(
        {
            platform_name,
            platform_logo_url,
            project_name,
            user_display_name,
            user_timezone,
            user_locale,
            title: campaign.label,
            campaign_name: campaign.label,
            campaign_url:
                base_url.replace(/\/$/, "") +
                `/plugins/testmanagement/?group_id=${project_id}#!/campaigns/${campaign.id}`,
            base_url,
            artifact_links_types: artifact_links_types,
            testdefinition_tracker_id,
        },
        downloadDocx,
        campaign,
    );
}
