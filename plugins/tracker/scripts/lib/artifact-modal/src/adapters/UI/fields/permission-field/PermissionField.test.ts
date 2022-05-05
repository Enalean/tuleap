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

import { setCatalog } from "../../../../gettext-catalog";
import { getGrantedGroupsSelect, getIsUsedByDefaultCheckbox } from "./PermissionField";
import { PermissionFieldController } from "./PermissionFieldController";
import type { PermissionFieldControllerType } from "./PermissionFieldController";
import type { HostElement } from "./PermissionField";

describe("PermissionField", () => {
    let doc: Document, target: ShadowRoot, controller: PermissionFieldControllerType;

    beforeEach(() => {
        setCatalog({
            getString: (msgid): string => msgid,
        });

        doc = document.implementation.createHTMLDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;

        controller = PermissionFieldController(
            {
                field_id: 1060,
                label: "Permissions",
                required: true,
                values: {
                    ugroup_representations: [
                        {
                            id: "101_1",
                            label: "Project members",
                            uri: "uri/to/101_1",
                            key: "a_key",
                            short_name: "project_members",
                            users_uri: "users/uri",
                        },
                    ],
                },
            },
            {
                field_id: 1060,
                value: {
                    is_used_by_default: false,
                    granted_groups: [],
                },
            },
            false
        );
    });

    it('when the "is_used checkbox has been checked/unchecked, it should ask the controller to update its value', () => {
        jest.spyOn(controller, "setIsFieldUsedByDefault");

        const host = {
            controller,
            field_presenter: controller.buildPresenter(),
        } as unknown as HostElement;

        const update = getIsUsedByDefaultCheckbox(host);

        update(host, target);

        const checkbox = target.querySelector("[data-test=permission-field-checkbox]");
        if (!(checkbox instanceof HTMLInputElement)) {
            throw new Error("Permission checkbox not found in target");
        }

        checkbox.checked = true;
        checkbox.dispatchEvent(new Event("change"));

        expect(controller.setIsFieldUsedByDefault).toHaveBeenCalledWith(true);
        expect(host.field_presenter.is_used).toBe(true);
    });

    it('when the "granted_groups select has been updated, it should ask the controller to update its value', () => {
        jest.spyOn(controller, "setGrantedGroups");

        const host = {
            controller,
            field_presenter: controller.buildPresenter(),
        } as unknown as HostElement;

        const update = getGrantedGroupsSelect(host);
        update(host, target);

        const select = target.querySelector("[data-test=permission-field-select]");
        if (!(select instanceof HTMLSelectElement)) {
            throw new Error("Granted groups select not found in target");
        }

        const [first_option] = select.options;

        first_option.selected = true;
        select.dispatchEvent(new Event("change"));

        expect(controller.setGrantedGroups).toHaveBeenCalledWith([first_option.value]);
        expect(host.field_presenter.granted_groups).toHaveLength(1);
        expect(host.field_presenter.granted_groups).toContain(first_option.value);
    });
});
