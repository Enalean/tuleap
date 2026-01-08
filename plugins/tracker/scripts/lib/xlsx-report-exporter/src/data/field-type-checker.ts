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

import type { ArtifactReportResponseFieldValueWithExtraFields } from "../type";

export function isFieldTakenIntoAccount(
    field_value: ArtifactReportResponseFieldValueWithExtraFields,
): boolean {
    return (
        field_value.type !== "art_link" &&
        field_value.type !== "file" &&
        field_value.type !== "cross" &&
        field_value.type !== "perm" &&
        field_value.type !== "ttmstepdef" &&
        field_value.type !== "ttmstepexec" &&
        field_value.type !== "burndown" &&
        field_value.type !== "burnup" &&
        field_value.type !== "Encrypted"
    );
}
