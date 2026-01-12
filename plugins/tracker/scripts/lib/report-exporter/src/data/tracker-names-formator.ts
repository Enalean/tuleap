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

import type { OrganizedReportsData } from "../type";
import { TextCellWithMerges } from "../type";
import { extractFieldsLabels } from "./report-fields-labels-extractor";

export function formatTrackerNames(
    organized_data: OrganizedReportsData,
): ReadonlyArray<TextCellWithMerges> {
    const formatted_tracker_names: Array<TextCellWithMerges> = [];
    formatted_tracker_names.push(
        new TextCellWithMerges(
            organized_data.first_level.tracker_name,
            extractFieldsLabels(organized_data.first_level.artifact_representations).length,
        ),
    );

    if (organized_data.second_level) {
        formatted_tracker_names.push(
            new TextCellWithMerges(
                organized_data.second_level.tracker_name,
                extractFieldsLabels(organized_data.second_level.artifact_representations).length,
            ),
        );
    }

    if (organized_data.third_level) {
        formatted_tracker_names.push(
            new TextCellWithMerges(
                organized_data.third_level.tracker_name,
                extractFieldsLabels(organized_data.third_level.artifact_representations).length,
            ),
        );
    }

    return formatted_tracker_names;
}
