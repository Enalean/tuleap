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
import { TYPE_FILE } from "../../constants";
import QuickLookGlobal from "./QuickLookGlobal.vue";
import type { ItemFile, RootState } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

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
            props: {
                currently_previewed_item,
            },
            global: {
                ...getGlobalTestOptions({
                    state: {} as unknown as RootState,
                }),
                directives: {
                    "dompurify-html": jest.fn(),
                },
            },
        });

        wrapper.get("[id=item-description]");
        expect(wrapper.vm.get_description).toContain(
            currently_previewed_item.post_processed_description,
        );
    });
});
