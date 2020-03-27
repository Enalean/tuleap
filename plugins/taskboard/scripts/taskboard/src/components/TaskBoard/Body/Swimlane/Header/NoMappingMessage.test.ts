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
import NoMappingMessage from "./NoMappingMessage.vue";
import { Card } from "../../../../../type";
import { createTaskboardLocalVue } from "../../../../../helpers/local-vue-for-test";

describe("NoMappingMessage", () => {
    it("displays a message when card has a status", async () => {
        const wrapper = shallowMount(NoMappingMessage, {
            localVue: await createTaskboardLocalVue(),
            propsData: {
                card: {
                    mapped_list_value: { label: "YOLO" },
                } as Card,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
    it("displays another message when card has no status", async () => {
        const wrapper = shallowMount(NoMappingMessage, {
            localVue: await createTaskboardLocalVue(),
            propsData: {
                card: {
                    mapped_list_value: null,
                } as Card,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
