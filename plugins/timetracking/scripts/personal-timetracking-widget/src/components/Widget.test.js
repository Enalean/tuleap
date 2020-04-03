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
import Widget from "./Widget.vue";
import WidgetReadingMode from "./WidgetReadingMode.vue";
import WidgetWritingMode from "./WidgetWritingMode.vue";
import WidgetArtifactTable from "./WidgetArtifactTable.vue";
import { createStoreMock } from "../../../../../../src/scripts/vue-components/store-wrapper-jest.js";

const userId = 102;
function getPersonalWidgetInstance(store_options) {
    const store = createStoreMock(store_options);
    const component_options = {
        propsData: {
            userId,
        },
        mocks: { $store: store },
    };
    return shallowMount(Widget, component_options);
}

describe("Given a personal timetracking widget", () => {
    let store_options;
    beforeEach(() => {
        store_options = {
            state: {
                reading_mode: true,
            },
        };

        getPersonalWidgetInstance(store_options);
    });

    it("When reading mode is true, then reading should be displayed but not writing mode", () => {
        const wrapper = getPersonalWidgetInstance(store_options);
        expect(wrapper.contains(WidgetReadingMode)).toBeTruthy();
        expect(wrapper.contains(WidgetWritingMode)).toBeFalsy();
        expect(wrapper.contains(WidgetArtifactTable)).toBeTruthy();
    });

    it("When reading mode is false, then writing should be displayed but not reading mode", () => {
        store_options.state.reading_mode = false;
        const wrapper = getPersonalWidgetInstance(store_options);

        expect(wrapper.contains(WidgetReadingMode)).toBeFalsy();
        expect(wrapper.contains(WidgetWritingMode)).toBeTruthy();
    });
});
