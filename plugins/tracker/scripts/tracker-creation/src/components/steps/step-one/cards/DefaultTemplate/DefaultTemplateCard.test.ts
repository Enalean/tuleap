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
import DefaultTemplateCard from "./DefaultTemplateCard.vue";
import { Tracker } from "../../../../../store/type";

describe("DefaultTemplateCard", () => {
    it("Displays a card for bug tracker", () => {
        const wrapper = shallowMount(DefaultTemplateCard, {
            propsData: {
                tracker: {
                    id: "default-bug",
                    name: "Bug",
                    description: "Bugs",
                    tlp_color: "fiesta-red",
                } as Tracker,
            },
        });
        expect(wrapper.element).toMatchSnapshot();
    });
    it("Displays a card for activity tracker", () => {
        const wrapper = shallowMount(DefaultTemplateCard, {
            propsData: {
                tracker: {
                    id: "default-activity",
                    name: "Activity",
                    description: "Activities",
                    tlp_color: "clockwork-orange",
                } as Tracker,
            },
        });
        expect(wrapper.element).toMatchSnapshot();
    });
});
