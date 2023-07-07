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

import { selectOrThrow } from "@tuleap/dom";
import { setCatalog } from "../../../../gettext-catalog";
import type { HostElement } from "./PermissionField";
import {
    getGrantedGroupsSelect,
    getIsUsedByDefaultCheckbox,
    PermissionField,
} from "./PermissionField";
import { PermissionFieldController } from "./PermissionFieldController";

describe("PermissionField", () => {
    let doc: Document, is_required: boolean;
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });

        doc = document.implementation.createHTMLDocument();
        is_required = true;
    });

    const getHost = (): HostElement => {
        const element = doc.createElement("div");
        const controller = PermissionFieldController(
            {
                field_id: 1060,
                label: "Permissions",
                required: is_required,
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
        return Object.assign(element, {
            controller,
            field_presenter: controller.buildPresenter(),
        } as HostElement);
    };

    it('when the "is_used checkbox has been checked/unchecked, it should ask the controller to update its value', () => {
        const host = getHost();
        const setIsFieldUsedByDefault = jest.spyOn(host.controller, "setIsFieldUsedByDefault");

        const update = getIsUsedByDefaultCheckbox(host);
        update(host, host);

        const checkbox = selectOrThrow(
            host,
            "[data-test=permission-field-checkbox]",
            HTMLInputElement
        );
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event("change"));

        expect(setIsFieldUsedByDefault).toHaveBeenCalledWith(true);
        expect(host.field_presenter.is_used).toBe(true);
    });

    it('when the "granted_groups select has been updated, it should ask the controller to update its value', () => {
        const host = getHost();
        const setGrantedGroups = jest.spyOn(host.controller, "setGrantedGroups");

        const update = getGrantedGroupsSelect(host);
        update(host, host);

        const select = selectOrThrow(
            host,
            "[data-test=permission-field-select]",
            HTMLSelectElement
        );
        const [first_option] = select.options;

        first_option.selected = true;
        select.dispatchEvent(new Event("change"));

        expect(setGrantedGroups).toHaveBeenCalledWith([first_option.value]);
        expect(host.field_presenter.granted_groups).toHaveLength(1);
        expect(host.field_presenter.granted_groups).toContain(first_option.value);
    });

    const render = (host: HostElement): ShadowRoot => {
        const update = PermissionField.content(host);
        update(host, host);
        return host as unknown as ShadowRoot;
    };

    it(`dispatches a bubbling "change" event when its checkbox is changed
        so that the modal shows a warning when closed`, () => {
        is_required = false;
        const host = getHost();
        const target = render(host);
        let is_bubbling = false;
        host.addEventListener("change", (event) => {
            is_bubbling = event.bubbles;
        });
        const checkbox = selectOrThrow(
            target,
            "[data-test=permission-field-checkbox]",
            HTMLInputElement
        );
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event("change", { bubbles: true }));

        expect(is_bubbling).toBe(true);
    });

    it(`dispatches a bubbling "change" event when its selectbox is changed
        so that the modal shows a warning when closed`, () => {
        const host = getHost();
        const target = render(host);
        let is_bubbling = false;
        host.addEventListener("change", (event) => {
            is_bubbling = event.bubbles;
        });
        const select = selectOrThrow(
            target,
            "[data-test=permission-field-select]",
            HTMLSelectElement
        );
        const [first_option] = select.options;
        first_option.selected = true;
        select.dispatchEvent(new Event("change", { bubbles: true }));

        expect(is_bubbling).toBe(true);
    });
});
