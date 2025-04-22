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

import { mount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import CollapsedSwimlane from "./CollapsedSwimlane.vue";
import SwimlaneHeader from "./Header/SwimlaneHeader.vue";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import type { Card, Swimlane } from "../../../../type";
import type { RootState } from "../../../../store/type";

describe("CollapsedSwimlane", () => {
    const mock_expand_swimlane = jest.fn();
    function wrapperFactory(): VueWrapper<InstanceType<typeof CollapsedSwimlane>> {
        return mount(CollapsedSwimlane, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        swimlane: {
                            getters: {
                                taskboard_cell_swimlane_header_classes: () => "",
                            },
                            actions: {
                                expandSwimlane: mock_expand_swimlane,
                            },
                            namespaced: true,
                        },
                    },
                    state: {
                        backlog_items_have_children: true,
                    } as RootState,
                }),
            },
            props: {
                swimlane: {
                    card: {
                        color: "fiesta-red",
                        label: "taskboard-swimlane",
                    } as Card,
                } as Swimlane,
            },
            stubs: { "card-xref-label": true },
        });
    }

    it("displays a toggle icon and a card with minimal information", async () => {
        const wrapper = await wrapperFactory();
        expect(wrapper.element).toMatchSnapshot();
    });

    it("expand the swimlane when user click on the toggle icon", async () => {
        const wrapper = await wrapperFactory();

        wrapper.findComponent(SwimlaneHeader).get("[data-test=swimlane-toggle]").trigger("click");
        expect(mock_expand_swimlane).toHaveBeenCalled();
    });
});
