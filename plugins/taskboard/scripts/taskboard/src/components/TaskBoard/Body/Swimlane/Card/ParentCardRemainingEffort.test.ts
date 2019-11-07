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
import ParentCardRemainingEffort from "./ParentCardRemainingEffort.vue";
import { Card } from "../../../../../type";

describe("ParentCardRemainingEffort", () => {
    it("displays the remaining effort of the parent card in a badge", () => {
        const wrapper = shallowMount(ParentCardRemainingEffort, {
            propsData: {
                card: {
                    remaining_effort: { value: 666 },
                    color: "lake-placid-blue"
                } as Card
            }
        });
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays nothing if the parent card has no remaining effort field", () => {
        const wrapper = shallowMount(ParentCardRemainingEffort, {
            propsData: {
                card: {
                    remaining_effort: null,
                    color: "lake-placid-blue"
                } as Card
            }
        });
        expect(wrapper.isEmpty()).toBe(true);
    });

    it("displays nothing if the parent card has no remaining effort value", () => {
        const wrapper = shallowMount(ParentCardRemainingEffort, {
            propsData: {
                card: {
                    remaining_effort: { value: null },
                    color: "lake-placid-blue"
                } as Card
            }
        });
        expect(wrapper.isEmpty()).toBe(true);
    });
});
