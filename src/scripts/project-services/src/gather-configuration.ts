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

import { getAttributeOrThrow } from "@tuleap/dom";
import type { AllowedIcons } from "./type";

type Configuration = {
    readonly project_id: string;
    readonly minimal_rank: number;
    readonly csrf_token: string;
    readonly csrf_token_name: string;
    readonly allowed_icons: AllowedIcons;
};

export function gatherConfiguration(vue_mount_point: HTMLElement): Configuration {
    const project_id = getAttributeOrThrow(vue_mount_point, "data-project-id");
    const minimal_rank = Number.parseInt(
        getAttributeOrThrow(vue_mount_point, "data-minimal-rank"),
        10,
    );
    const csrf_token = getAttributeOrThrow(vue_mount_point, "data-csrf-token");
    const csrf_token_name = getAttributeOrThrow(vue_mount_point, "data-csrf-token-name");
    const allowed_icons = JSON.parse(getAttributeOrThrow(vue_mount_point, "data-allowed-icons"));

    return { project_id, minimal_rank, csrf_token, csrf_token_name, allowed_icons };
}
