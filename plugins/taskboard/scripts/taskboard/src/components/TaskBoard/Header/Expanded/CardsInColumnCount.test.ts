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
import { ColumnDefinition } from "../../../../type";
import { createStoreMock } from "../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";

describe("CardsInColumnCount", () => {
    it("Displays the number of cards in the given column", () => {
        const wrapper = shallowMount(CardsInColumnCount, {
            propsData: {
                column: {} as ColumnDefinition,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        swimlane: {},
                    },
                    getters: {
                        "swimlane/is_loading_cards": false,
                        "swimlane/nb_cards_in_column": (): number => 4,
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
            propsData: {
                column: {} as ColumnDefinition,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        swimlane: {},
                    },
                    getters: {
                        "swimlane/is_loading_cards": true,
                        "swimlane/nb_cards_in_column": (): number => 4,
                    },
                }),
            },
        });

        expect(wrapper.classes("taskboard-header-count")).toBe(true);
        expect(wrapper.classes("taskboard-header-count-loading")).toBe(true);
        expect(wrapper.text()).toBe("4");
    });
});
