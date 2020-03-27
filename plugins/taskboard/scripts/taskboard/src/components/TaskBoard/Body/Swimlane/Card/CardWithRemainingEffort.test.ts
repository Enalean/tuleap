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
import CardWithRemainingEffort from "./CardWithRemainingEffort.vue";
import ParentCardRemainingEffort from "./ParentCardRemainingEffort.vue";
import ParentCard from "./ParentCard.vue";
import EditCardButtons from "./EditMode/EditCardButtons.vue";

describe("CardWithRemainingEffort", () => {
    it("displays the solo card in its own cell", () => {
        const wrapper = shallowMount(CardWithRemainingEffort, {
            propsData: {
                card: {
                    id: 43,
                    remaining_effort: 2.5,
                },
            },
        });

        expect(wrapper.contains(ParentCard)).toBe(true);
        expect(wrapper.contains(ParentCardRemainingEffort)).toBe(true);
        expect(wrapper.contains(EditCardButtons)).toBe(true);
    });
});
