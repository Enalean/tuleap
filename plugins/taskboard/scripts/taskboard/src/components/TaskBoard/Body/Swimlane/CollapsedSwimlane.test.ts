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
import type { Wrapper } from "@vue/test-utils";
import CollapsedSwimlane from "./CollapsedSwimlane.vue";
import SwimlaneHeader from "./Header/SwimlaneHeader.vue";
import { createTaskboardLocalVue } from "../../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { ColumnDefinition, Swimlane } from "../../../../type";
import type { RootState } from "../../../../store/type";

type UnknowObject = Record<string, unknown>;

async function wrapperFactory(
    $store?: unknown,
    props?: UnknowObject,
): Promise<Wrapper<CollapsedSwimlane>> {
    const defined_store =
        $store ??
        createStoreMock({
            state: {
                swimlane: {},
                column: { columns: [] as ColumnDefinition[] },
                backlog_items_have_children: true,
            } as RootState,
            getters: { "swimlane/taskboard_cell_swimlane_header_classes": "" },
        });
    const defined_props = props ?? {
        swimlane: {
            card: {
                color: "fiesta-red",
                label: "taskboard-swimlane",
            },
        },
    };
    return mount(CollapsedSwimlane, {
        localVue: await createTaskboardLocalVue(),
        mocks: { $store: defined_store },
        propsData: { ...defined_props },
        stubs: { "card-xref-label": true },
    });
}

describe("CollapsedSwimlane", () => {
    it("displays a toggle icon and a card with minimal information", async () => {
        const wrapper = await wrapperFactory();
        expect(wrapper.element).toMatchSnapshot();
    });

    it("expand the swimlane when user click on the toggle icon", async () => {
        const $store = createStoreMock({
            state: {
                swimlane: {},
                column: { columns: [] as ColumnDefinition[] },
                backlog_items_have_children: true,
            } as RootState,
            getters: { "swimlane/taskboard_cell_swimlane_header_classes": "" },
        });
        const swimlane: Swimlane = {
            card: {
                color: "fiesta-red",
                label: "taskboard-swimlane",
            },
        } as Swimlane;
        const wrapper = await wrapperFactory($store, { swimlane });

        wrapper.findComponent(SwimlaneHeader).get("[data-test=swimlane-toggle]").trigger("click");
        expect($store.dispatch).toHaveBeenCalledWith("swimlane/expandSwimlane", swimlane);
    });
});
