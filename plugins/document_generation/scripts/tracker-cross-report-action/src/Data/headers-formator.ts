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
import type { HeadersSection } from "./data-formator";
import { formatTrackerNames } from "./tracker-names-formattor";
import { formatReportsFieldsLabels } from "./reports-fields-labels-formator";

export function formatHeaders(organized_data: OrganizedReportsData): HeadersSection {
    if (organized_data.first_level.artifact_representations.size === 0) {
        throw new Error("This must not happen. Check must be done before.");
    }

    return {
        reports_fields_labels: formatReportsFieldsLabels(organized_data),
        tracker_names: formatTrackerNames(organized_data),
    };
}
