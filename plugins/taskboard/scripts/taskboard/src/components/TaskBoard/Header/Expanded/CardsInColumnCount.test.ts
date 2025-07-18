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
import CardsInColumnCount from "./CardsInColumnCount.vue";
import type { ColumnDefinition } from "../../../../type";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("CardsInColumnCount", () => {
    it("Displays the number of cards in the given column", () => {
        const wrapper = shallowMount(CardsInColumnCount, {
            props: {
                column: {} as ColumnDefinition,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        swimlane: {
                            namespaced: true,
                            getters: {
                                is_loading_cards: () => false,
                                nb_cards_in_column: () => (): number => 4,
                            },
                            actions: {
                                loadSwimlanes: jest.fn(),
                            },
                        },
                    },
                }),
            },
        });

        expect(wrapper.classes("taskboard-header-count")).toBe(true);
        expect(wrapper.classes("taskboard-header-count-loading")).toBe(false);
        expect(wrapper.text()).toBe("4");
    });

    it("Add loading class if we are still counting elements", () => {
        const wrapper = shallowMount(CardsInColumnCount, {
            props: {
                column: {} as ColumnDefinition,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        swimlane: {
                            namespaced: true,
                            getters: {
                                is_loading_cards: () => (): boolean => true,
                                nb_cards_in_column: () => (): number => 4,
                            },
                            actions: {
                                loadSwimlanes: jest.fn(),
                            },
                        },
                    },
                }),
            },
        });

        expect(wrapper.classes("taskboard-header-count")).toBe(true);
        expect(wrapper.classes("taskboard-header-count-loading")).toBe(true);
        expect(wrapper.text()).toBe("4");
    });
});
