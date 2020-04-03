/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import DefaultTemplateSection from "./DefaultTemplateSection.vue";
import { State, Tracker } from "../../../../../store/type";
import { createStoreMock } from "../../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";

describe("DefaultTemplateSection", () => {
    it("Displays cards for default templates trackers", () => {
        const wrapper = shallowMount(DefaultTemplateSection, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        default_templates: [{} as Tracker, {} as Tracker],
                    } as State,
                }),
            },
        });
        expect(wrapper.element).toMatchSnapshot();
    });
});
