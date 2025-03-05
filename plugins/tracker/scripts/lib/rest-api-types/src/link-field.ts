/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import type { ArtifactLinkFieldIdentifier, LinkDirection } from "@tuleap/plugin-tracker-constants";
import type { BaseFieldStructure, PermissionsArray } from "./trackers";

export interface TrackerUsedArtifactLinkResponse {
    readonly shortname: string;
    readonly forward_label: string;
}

export interface LinkTypeRepresentation {
    readonly shortname: string;
    readonly direction: LinkDirection;
    readonly label: string;
}

export interface AllowedLinkTypeRepresentation {
    readonly shortname: string;
    readonly forward_label: string;
    readonly reverse_label: string;
}

export interface ArtifactLinkFieldStructure extends BaseFieldStructure {
    readonly type: ArtifactLinkFieldIdentifier;
    readonly label: string;
    readonly allowed_types: ReadonlyArray<AllowedLinkTypeRepresentation>;
    readonly permissions: PermissionsArray;
}
