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

export function buildCreateModalCallback(vue_mount_point_id, RootComponent) {
    const vue_mount_point = document.getElementById(vue_mount_point_id);

    if (!vue_mount_point) {
        throw new Error(`Could not find Vue mount point ${vue_mount_point_id}`);
    }
    const { projectId, minimalRank, csrfToken, csrfTokenName, allowedIcons } =
        vue_mount_point.dataset;

    return () => {
        return new RootComponent({
            propsData: {
                project_id: projectId,
                minimal_rank: Number.parseInt(minimalRank, 10),
                csrf_token_name: csrfTokenName,
                csrf_token: csrfToken,
                allowed_icons: JSON.parse(allowedIcons),
            },
        }).$mount(vue_mount_point);
    };
}
