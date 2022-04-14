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

import type { ExportSettings } from "../../export-document";

export function generateFilename(export_settings: ExportSettings): string {
    let filename: string =
        export_settings.first_level.tracker_name + "-" + export_settings.first_level.report_name;

    if (export_settings.second_level) {
        filename += "-" + export_settings.second_level.tracker_name;
    }

    if (export_settings.third_level) {
        filename += "-" + export_settings.third_level.tracker_name;
    }

    filename += ".xlsx";

    return filename;
}
