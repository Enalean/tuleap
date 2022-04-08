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
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";

const observe = jest.fn();

window.ResizeObserver =
    window.ResizeObserver ||
    jest.fn().mockImplementation(() => ({
        observe,
        unobserve: jest.fn(),
    }));

describe("SearchItemDropdown", () => {
    let wrapper: Wrapper<SearchItemDropdown>;
    let $store = {
        dispatch: jest.fn(),
    };
    let parent_container: HTMLElement;
    let addEventListener: jest.SpyInstance;

    beforeEach(() => {
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
        parent_container.classList.add("document-search-table-container");
        parent_container.appendChild(mount_point);
        addEventListener = jest.spyOn(parent_container, "addEventListener");

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

    it("should observe the resize of the body to adapt the position of trigger button", () => {
        expect(observe).toHaveBeenCalledWith(document.body);
    });

    it("should observe the scroll of the table container to adapt the position of trigger button", () => {
        expect(addEventListener).toHaveBeenCalledWith("scroll", expect.anything(), {
            passive: true,
        });
    });

    it("should display the menu as soon as the user click on the trigger and the real item is loaded", () => {
        return new Promise((done) => {
            wrapper.find("[data-test=trigger").trigger("click");

            process.nextTick(() => {
                expect(wrapper.find("[data-test=spinner]").exists()).toBe(false);
                expect(wrapper.findComponent(DropDownMenuTreeView).exists()).toBe(true);
                done(null);
            });
        });
    });
});
