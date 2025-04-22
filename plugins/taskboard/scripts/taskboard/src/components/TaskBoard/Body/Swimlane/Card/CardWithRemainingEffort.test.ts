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
import type { Card, RemainingEffort } from "../../../../../type";

describe("CardWithRemainingEffort", () => {
    it("displays the solo card in its own cell", () => {
        const wrapper = shallowMount(CardWithRemainingEffort, {
            props: {
                card: {
                    id: 43,
                    remaining_effort: { value: 2.5 } as RemainingEffort,
                } as Card,
            },
        });

        expect(wrapper.findComponent(ParentCard).exists()).toBe(true);
        expect(wrapper.findComponent(ParentCardRemainingEffort).exists()).toBe(true);
        expect(wrapper.findComponent(EditCardButtons).exists()).toBe(true);
    });

    it("focuses the card when receiving the `editor-closed` event", () => {
        const wrapper = shallowMount(CardWithRemainingEffort, {
            attachTo: document.body,
            props: {
                card: {
                    id: 43,
                    remaining_effort: { value: 2.5 } as RemainingEffort,
                } as Card,
            },
        });

        const parent_card = wrapper.findComponent(ParentCard);
        parent_card.vm.$emit("editor-closed");

        if (!(document.activeElement instanceof HTMLElement)) {
            throw new Error("Active element should be the CardWithRemainingEffort element");
        }
        expect(document.activeElement.dataset.test).toBe("card-with-remaining-effort");
    });
});
