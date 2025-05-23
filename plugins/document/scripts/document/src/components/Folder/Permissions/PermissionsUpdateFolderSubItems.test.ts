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

import { describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";

import PermissionsUpdateFolderSubItems from "./PermissionsUpdateFolderSubItems.vue";
import { TYPE_FOLDER, TYPE_EMPTY } from "../../../constants";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import emitter from "../../../helpers/emitter";
import type { Empty, Folder, Item } from "../../../type";

describe("PermissionsUpdateFolderSubItems", () => {
    function getWrapper(
        item: Item,
        value = false,
    ): VueWrapper<InstanceType<typeof PermissionsUpdateFolderSubItems>> {
        return shallowMount(PermissionsUpdateFolderSubItems, {
            props: {
                item,
                value,
            },
            global: { ...getGlobalTestOptions({}) },
        });
    }

    it("Visible when item is a folder", () => {
        const item = { type: TYPE_FOLDER } as Folder;
        const wrapper = getWrapper(item);

        expect(wrapper.html()).toBeTruthy();
    });

    it("Not visible when item is not a folder", () => {
        const item = { type: TYPE_EMPTY } as Empty;
        const wrapper = getWrapper(item);

        expect(wrapper.html()).toMatchInlineSnapshot(`<!--v-if-->`);
    });

    it.each([
        ["checked", true],
        ["unchecked", false],
    ])(
        'When the checkbox is %s, then it should emit a "update-apply-permissions-on-children" event containing the checkbox state.',
        (state, is_checked) => {
            const emit = vi.spyOn(emitter, "emit");
            const wrapper = getWrapper({ type: TYPE_FOLDER } as Folder);
            const checkbox = wrapper.find<HTMLInputElement>(
                "[data-test=checkbox-apply-permissions-on-children]",
            );

            checkbox.setValue(is_checked);
            checkbox.trigger("input");
            expect(emit).toHaveBeenCalledWith("update-apply-permissions-on-children", {
                do_permissions_apply_on_children: is_checked,
            });
        },
    );

    it.each([
        [false, "unchecked"],
        [true, "checked"],
    ])("When its value is %s, then the checkbox input should be %s", (value) => {
        const wrapper = getWrapper({ type: TYPE_FOLDER } as Folder, value);
        const checkbox = wrapper.find<HTMLInputElement>(
            "[data-test=checkbox-apply-permissions-on-children]",
        );
        expect(checkbox.element.checked).toBe(value);
    });
});
