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
import CardInitialEffort from "./CardInitialEffort.vue";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";
import type { Card } from "../../../../../type";

describe("CardInitialEffort", () => {
    it("displays the initial effort of the card in a badge", () => {
        const wrapper = shallowMount(CardInitialEffort, {
            global: { ...getGlobalTestOptions({}) },
            props: {
                card: {
                    initial_effort: 666,
                    color: "lake-placid-blue",
                } as Card,
            },
        });
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays nothing if the card has no initial effort", () => {
        const wrapper = shallowMount(CardInitialEffort, {
            global: { ...getGlobalTestOptions({}) },
            props: {
                card: {
                    initial_effort: null,
                    color: "lake-placid-blue",
                } as Card,
            },
        });
        expect(wrapper.html()).toBe("<!--v-if-->");
    });
});
