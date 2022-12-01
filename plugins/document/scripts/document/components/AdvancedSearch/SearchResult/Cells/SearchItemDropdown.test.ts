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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SearchItemDropdown from "./SearchItemDropdown.vue";
import localVue from "../../../../helpers/local-vue";
import type { Item, ItemSearchResult } from "../../../../type";
import DropDownMenuTreeView from "../../../Folder/DropDown/DropDownMenuTreeView.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import * as tlp_dropdown from "@tuleap/tlp-dropdown";
import { EVENT_TLP_DROPDOWN_SHOWN } from "@tuleap/tlp-dropdown";

jest.mock("@tuleap/tlp-dropdown");

const observe = jest.fn();

window.ResizeObserver =
    window.ResizeObserver ||
    jest.fn().mockImplementation(() => ({
        observe,
        unobserve: jest.fn(),
    }));

describe("SearchItemDropdown", () => {
    let fake_dropdown_object: Dropdown;
    let wrapper: Wrapper<SearchItemDropdown>;
    let $store = {
        dispatch: jest.fn(),
    };
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
            removeEventListener: jest.fn(),
        } as unknown as Dropdown;
        jest.spyOn(tlp_dropdown, "createDropdown").mockReturnValue(fake_dropdown_object);

        $store = createStoreMock({});
        $store.dispatch.mockImplementation((action) => {
            if (action === "loadDocument") {
                return Promise.resolve({
                    id: 111,
                } as Item);
            }
        });

        const mount_point = document.createElement("div");

        parent_container = document.createElement("div");
        parent_container.appendChild(mount_point);

        document.body.appendChild(parent_container);

        wrapper = shallowMount(SearchItemDropdown, {
            localVue,
            propsData: {
                item: {
                    id: 111,
                } as ItemSearchResult,
            },
            mocks: {
                $store,
            },
            attachTo: mount_point,
        });
    });

    afterEach(() => {
        wrapper.destroy();
    });

    it("should render the dropdown with the menu detached", () => {
        expect(document.body).toMatchSnapshot();
    });

    it("should display a spinner if real item is not loaded", () => {
        expect(wrapper.find("[data-test=spinner]").exists()).toBe(true);
        expect(wrapper.findComponent(DropDownMenuTreeView).exists()).toBe(false);
    });

    it("should display the menu as soon as the user open the dropdown and the real item is loaded", async () => {
        await dropdown_shown_callback();

        expect(wrapper.find("[data-test=spinner]").exists()).toBe(false);
        expect(wrapper.findComponent(DropDownMenuTreeView).exists()).toBe(true);
    });
});
