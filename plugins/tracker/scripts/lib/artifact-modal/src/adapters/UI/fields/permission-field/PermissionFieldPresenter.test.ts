/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { PermissionFieldPresenter } from "./PermissionFieldPresenter";

describe("PermissionFieldPresenter", () => {
    it("should build from field", () => {
        const user_groups = [
            {
                id: "101_1",
                label: "Project members",
                uri: "uri/to/101_1",
                key: "a_key",
                short_name: "project_members",
                users_uri: "users/uri",
            },
        ];
        const granted_groups = ["101_1"];
        const presenter = PermissionFieldPresenter.fromField(
            {
                field_id: 1060,
                label: "Permissions",
                required: true,
                values: {
                    ugroup_representations: user_groups,
                },
            },
            granted_groups,
            false,
            true,
            false,
            false,
        );

        expect(presenter.field_id).toBe(1060);
        expect(presenter.label).toBe("Permissions");
        expect(presenter.is_field_required).toBe(true);
        expect(presenter.user_groups).toBe(user_groups);
        expect(presenter.granted_groups).toBe(granted_groups);
        expect(presenter.is_field_disabled).toBe(false);
        expect(presenter.is_used).toBe(true);
        expect(presenter.is_select_box_required).toBe(false);
        expect(presenter.is_select_box_disabled).toBe(false);
    });
});
