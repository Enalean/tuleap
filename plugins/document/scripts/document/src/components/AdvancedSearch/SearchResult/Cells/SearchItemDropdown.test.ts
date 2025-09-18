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

import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SearchItemDropdown from "./SearchItemDropdown.vue";
import type { Item, ItemSearchResult } from "../../../../type";
import DropDownMenuTreeView from "../../../Folder/DropDown/DropDownMenuTreeView.vue";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import * as tlp_dropdown from "@tuleap/tlp-dropdown";
import { EVENT_TLP_DROPDOWN_SHOWN } from "@tuleap/tlp-dropdown";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

vi.mock("@tuleap/tlp-dropdown");

const observe = vi.fn();

window.ResizeObserver =
    window.ResizeObserver ||
    vi.fn().mockImplementation(() => ({
        observe,
        unobserve: vi.fn(),
    }));

describe("SearchItemDropdown", () => {
    let fake_dropdown_object: Dropdown;
    let wrapper: VueWrapper<InstanceType<typeof SearchItemDropdown>>;
    let parent_container: HTMLElement;
    let dropdown_shown_callback: () => Promise<void>;

    beforeEach(() => {
        fake_dropdown_object = {
            listeners: [],
            addEventListener: (event: string, callback: () => Promise<void>) => {
                if (event === EVENT_TLP_DROPDOWN_SHOWN) {
                    dropdown_shown_callback = callback;
                }
            },
            removeEventListener: vi.fn(),
        } as unknown as Dropdown;
        vi.spyOn(tlp_dropdown, "createDropdown").mockReturnValue(fake_dropdown_object);

        const loadDocument = (): Promise<Item> =>
            Promise.resolve({
                id: 111,
            } as Item);

        const mount_point = document.createElement("div");

        parent_container = document.createElement("div");
        parent_container.appendChild(mount_point);

        document.body.appendChild(parent_container);

        wrapper = shallowMount(SearchItemDropdown, {
            props: {
                item: {
                    id: 111,
                } as ItemSearchResult,
            },
            global: {
                ...getGlobalTestOptions({
                    actions: {
                        loadDocument,
                    },
                }),
            },
            attachTo: mount_point,
        });
    });

    afterEach(() => {
        wrapper.unmount();
    });

    it("should render the dropdown with the menu detached", () => {
        expect(document.body).toMatchSnapshot();
    });

    it("should display a spinner if real item is not loaded", () => {
        expect(wrapper.vm.should_menu_be_displayed).toBe(false);
        expect(wrapper.findComponent(DropDownMenuTreeView).exists()).toBe(false);
    });

    it("should display the menu as soon as the user open the dropdown and the real item is loaded", async () => {
        await dropdown_shown_callback();

        expect(wrapper.vm.should_menu_be_displayed).toBe(true);
        expect(wrapper.findComponent(DropDownMenuTreeView).exists()).toBe(true);
    });
});
