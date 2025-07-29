/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

export function buildCreateModalCallback(vue_mount_point_id, RootComponent) {
    const vue_mount_point = document.getElementById(vue_mount_point_id);

    if (!vue_mount_point) {
        throw Error(`Could not find Vue mount point ${vue_mount_point_id}`);
    }
    const project_id = getAttributeOrThrow(vue_mount_point, "data-project-id");
    const minimal_rank = Number.parseInt(
        getAttributeOrThrow(vue_mount_point, "data-minimal-rank"),
        10,
    );
    const csrf_token = getAttributeOrThrow(vue_mount_point, "data-csrf-token");
    const csrf_token_name = getAttributeOrThrow(vue_mount_point, "data-csrf-token-name");
    const allowed_icons = JSON.parse(getAttributeOrThrow(vue_mount_point, "data-allowed-icons"));

    return () => {
        return new RootComponent({
            propsData: {
                project_id,
                minimal_rank,
                csrf_token_name,
                csrf_token,
                allowed_icons,
            },
        }).$mount(vue_mount_point);
    };
}
