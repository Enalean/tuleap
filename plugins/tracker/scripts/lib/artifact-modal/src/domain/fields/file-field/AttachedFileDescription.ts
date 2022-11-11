/*
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

/**
 * I hold a transformed representation of the File field's value at the last
 * changeset of the current artifact.
 */
export interface AttachedFileDescription {
    readonly id: number;
    readonly name: string;
    readonly description: string;
    readonly submitted_by: number; // User identifier
    readonly size: number; // In bytes
    readonly html_preview_url: string | null;
    readonly html_url: string;
    readonly display_as_image: boolean;
    marked_for_removal: boolean;
}
