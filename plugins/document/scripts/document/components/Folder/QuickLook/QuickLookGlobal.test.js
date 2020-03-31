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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue";
import { createStoreMock } from "../../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";
import { TYPE_EMPTY } from "../../../constants.js";
import QuickLookGlobal from "./QuickLookGlobal.vue";

describe("QuickLookGlobal", () => {
    it(`Displays the description of the item observed in the QuickLook`, () => {
        const state = {};
        const store_options = {
            state,
        };
        const store = createStoreMock(store_options);

        const item = {
            id: 42,
            lock_info: null,
            type: TYPE_EMPTY,
            description: "description with ref #1",
            post_processed_description:
                'description with <a href="https://example.com/goto">ref #1</a>',
        };

        store.state.currently_previewed_item = item;

        const wrapper = shallowMount(QuickLookGlobal, {
            localVue,
            mocks: { $store: store },
        });

        const displayed_description = wrapper.get("[id=item-description]");

        expect(displayed_description.text()).toEqual(item.description);
        expect(displayed_description.html()).toContain(item.post_processed_description);
    });
});
