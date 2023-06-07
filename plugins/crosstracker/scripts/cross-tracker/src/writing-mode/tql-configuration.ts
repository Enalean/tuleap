/**
 * Copyright (c) 2017 - Present, Enalean. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

import { buildModeDefinition } from "@tuleap/plugin-tracker-tql-codemirror";

const TQL_cross_tracker_autocomplete_keywords = [
    "AND",
    "OR",
    "OPEN()",
    "NOW()",
    "BETWEEN(",
    "IN(",
    "NOT",
    "MYSELF()",
    "WITH PARENT",
    "WITH PARENT ARTIFACT",
    "WITHOUT PARENT",
    "WITHOUT PARENT ARTIFACT",
    "@title",
    "@description",
    "@status",
    "@last_update_date",
    "@last_update_by",
    "@submitted_on",
    "@submitted_by",
    "@assigned_to",
];

const cross_tracker_allowed_keywords = {
    additional_keywords: [
        "@title",
        "@description",
        "@status",
        "@last_update_date",
        "@last_update_by",
        "@submitted_on",
        "@submitted_by",
        "@assigned_to",
    ],
};
const TQL_cross_tracker_mode_definition = buildModeDefinition(cross_tracker_allowed_keywords);

export { TQL_cross_tracker_autocomplete_keywords, TQL_cross_tracker_mode_definition };
