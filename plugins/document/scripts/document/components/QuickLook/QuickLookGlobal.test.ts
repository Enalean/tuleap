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
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { TYPE_FILE } from "../../constants";
import QuickLookGlobal from "./QuickLookGlobal.vue";
import type { ItemFile, State } from "../../type";
import localVue from "../../helpers/local-vue";

describe("QuickLookGlobal", () => {
    it(`Displays the description of the item observed in the QuickLook`, () => {
        const currently_previewed_item = {
            id: 42,
            lock_info: null,
            type: TYPE_FILE,
            description: "description with ref #1",
            post_processed_description:
                'description with <a href="https://example.com/goto">ref #1</a>',
        } as ItemFile;

        const wrapper = shallowMount(QuickLookGlobal, {
            localVue,
            mocks: {
                $store: createStoreMock({
                    state: {
                        currently_previewed_item,
                    } as unknown as State,
                }),
            },
        });

        const displayed_description = wrapper.get("[id=item-description]");
        expect(displayed_description.text()).toStrictEqual(currently_previewed_item.description);
        expect(displayed_description.html()).toContain(
            currently_previewed_item.post_processed_description
        );
    });
});
