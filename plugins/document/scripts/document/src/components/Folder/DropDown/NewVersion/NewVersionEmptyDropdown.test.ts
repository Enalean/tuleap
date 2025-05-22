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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import NewVersionEmptyDropdown from "./NewVersionEmptyDropdown.vue";
import type { Item } from "../../../../type";
import * as tlp_dropdown from "@tuleap/tlp-dropdown";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
vi.mock("@tuleap/tlp-dropdown");

describe("NewVersionEmptyDropdown", function () {
    let fake_dropdown_object: Dropdown;
    let createDropdown: vi.SpyInstance;

    beforeEach(() => {
        fake_dropdown_object = {} as Dropdown;

        createDropdown = vi.spyOn(tlp_dropdown, "createDropdown");
        createDropdown.mockReturnValue(fake_dropdown_object);
    });

    it("should initiate a dropdown", function () {
        shallowMount(NewVersionEmptyDropdown, {
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
});
