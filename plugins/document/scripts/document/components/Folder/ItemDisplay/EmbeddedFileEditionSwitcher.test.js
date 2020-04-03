/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import EmbeddedFileEditionSwitcher from "./EmbeddedFileEditionSwitcher.vue";

describe("EmbeddedFileEditionSwitcher", () => {
    let factory, store;

    beforeEach(() => {
        const store_options = {};

        store = createStoreMock(store_options);

        factory = (props = {}) => {
            return shallowMount(EmbeddedFileEditionSwitcher, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Given user is not in large view
        Then switch button should be check on narrow`, () => {
        store.state.is_embedded_in_large_view = false;
        const wrapper = factory();

        expect(wrapper.get("[data-test=view-switcher-narrow]").element.checked).toBe(true);
        expect(wrapper.get("[data-test=view-switcher-large]").element.checked).toBe(false);
    });

    it(`Embedded document is well rendered in narrow mode`, () => {
        store.state.is_embedded_in_large_view = true;
        const wrapper = factory();

        expect(wrapper.get("[data-test=view-switcher-narrow]").element.checked).toBe(false);
        expect(wrapper.get("[data-test=view-switcher-large]").element.checked).toBe(true);
    });

    it(`Should switch view to narrow when user click on narrow view`, () => {
        store.state.currently_previewed_item = { id: 42, title: "my embedded document" };
        store.state.is_embedded_in_large_view = false;
        const wrapper = factory();

        wrapper.get("[data-test=view-switcher-narrow]").trigger("click");
        expect(store.dispatch).toHaveBeenCalledWith(
            "displayEmbeddedInNarrowMode",
            store.state.currently_previewed_item
        );
    });

    it(`Should switch view to large when user click on large view`, () => {
        store.state.currently_previewed_item = { id: 42, title: "my embedded document" };
        store.state.is_embedded_in_large_view = true;
        const wrapper = factory();

        wrapper.get("[data-test=view-switcher-large]").trigger("click");
        expect(store.dispatch).toHaveBeenCalledWith(
            "displayEmbeddedInLargeMode",
            store.state.currently_previewed_item
        );
    });
});
