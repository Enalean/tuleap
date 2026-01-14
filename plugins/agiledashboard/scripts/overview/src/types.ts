/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

export type ParentArtifact = {
    readonly id: number;
    readonly label: string;
};

export type StatusValue = {
    readonly value: string;
    readonly color: string | null;
};

export type Artifact = {
    readonly id: number;
    readonly label: string;
    readonly short_type: string;
    readonly status: string;
    readonly full_status: StatusValue;
    readonly color: string;
    readonly parent: ParentArtifact | null;
};
