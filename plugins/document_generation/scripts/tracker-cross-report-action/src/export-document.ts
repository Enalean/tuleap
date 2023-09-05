/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { ReportSection } from "./Data/data-formator";
import { formatData } from "./Data/data-formator";

interface ExportLevelSetting {
    readonly tracker_name: string;
    readonly report_id: number;
    readonly report_name: string;
    readonly table_renderer_id?: number | undefined;
    readonly artifact_link_types: ReadonlyArray<string>;
}

export interface ExportSettings {
    readonly first_level: ExportLevelSetting;
    readonly second_level?: ExportLevelSetting;
    readonly third_level?: Omit<ExportLevelSetting, "artifact_link_types">;
}

export async function downloadXLSXDocument(
    export_settings: ExportSettings,
    download_document: (export_settings: ExportSettings, formatted_data: ReportSection) => void,
): Promise<void> {
    const formatted_data: ReportSection = await formatData(export_settings);

    download_document(export_settings, formatted_data);
}
