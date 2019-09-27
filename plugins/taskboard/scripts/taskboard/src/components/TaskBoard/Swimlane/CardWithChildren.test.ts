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
import CardWithChildren from "./CardWithChildren.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";
import { Card, ColumnDefinition } from "../../../type";

describe("CardWithChildren", () => {
    it("displays the parent card in its own cell with columns skeletons", () => {
        const wrapper = shallowMount(CardWithChildren, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [
                            { id: 2, label: "To do" } as ColumnDefinition,
                            { id: 3, label: "Done" } as ColumnDefinition
                        ]
                    }
                })
            },
            propsData: {
                card: {
                    id: 43
                } as Card
            }
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
