/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import type { ArtifactLinkFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import type { ArtifactLinkFieldIdentifier } from "@tuleap/plugin-tracker-constants";

interface LinkFieldWithPermissions {
    readonly field_id: number;
    readonly type: ArtifactLinkFieldIdentifier;
    readonly permissions: readonly ["read", "create"?, "update"?];
}

export function formatExistingValue(field: ArtifactLinkFieldStructure): LinkFieldWithPermissions {
    const { field_id, type, permissions } = field;

    return { field_id, type, permissions };
}
