/**
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

import { shallowMount } from "@vue/test-utils";
import NewItemDropdown from "./NewItemDropdown.vue";
import type { Item } from "../../../../type";
import * as tlp_dropdown from "@tuleap/tlp-dropdown";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { TYPE_FOLDER } from "../../../../constants";
import { ItemType } from "../../../../type";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
jest.mock("@tuleap/tlp-dropdown");

describe("NewItemDropdown", function () {
    let fake_dropdown_object: Dropdown;
    let createDropdown: jest.SpyInstance;

    beforeEach(() => {
        fake_dropdown_object = {} as Dropdown;

        createDropdown = jest.spyOn(tlp_dropdown, "createDropdown");
        createDropdown.mockReturnValue(fake_dropdown_object);
    });

    it("should initiate a dropdown", function () {
        shallowMount(NewItemDropdown, {
            props: {
                item: {
                    type: "folder",
                    user_can_write: true,
                } as Item,
            },
            global: { ...getGlobalTestOptions({}) },
        });

        expect(createDropdown).toHaveBeenCalled();
    });

    it.each(ItemType.filter((type) => type !== TYPE_FOLDER))(
        "should not output anything since %s is not a folder",
        function (type) {
            const wrapper = shallowMount(NewItemDropdown, {
                props: {
                    item: {
                        type,
                        user_can_write: true,
                    } as Item,
                },
            });

            expect(wrapper.findAll("*")).toHaveLength(0);
        },
    );

    it("should not output anything if folder is not writable", function () {
        const wrapper = shallowMount(NewItemDropdown, {
            props: {
                item: {
                    type: "folder",
                    user_can_write: false,
                } as Item,
            },
        });

        expect(wrapper.findAll("*")).toHaveLength(0);
    });
});
