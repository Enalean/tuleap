/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import WidgetQueryEditor from "./WidgetQueryEditor.vue";
import WidgetQueryDisplayer from "./WidgetQueryDisplayer.vue";
import TimetrackingManagementWidget from "./TimetrackingManagementWidget.vue";
import { RETRIEVE_QUERY } from "../injection-symbols";
import type { Query } from "../query/QueryRetriever";
import { RetrieveQueryStub } from "../../tests/stubs/RetrieveQueryStub";
import WidgetQuerySaveRequest from "./WidgetQuerySaveRequest.vue";

describe("Given a Timetracking Management Widget", () => {
    let query_retriever: Query;
    function getTimetrackingManagementWidgetInstance(): VueWrapper {
        query_retriever = RetrieveQueryStub.withDefaults([]);

        return shallowMount(TimetrackingManagementWidget, {
            global: {
                provide: {
                    [RETRIEVE_QUERY.valueOf()]: query_retriever,
                },
            },
        });
    }

    it("When the query displayer is clicked, then the query editor should be displayed but not query displayer", async () => {
        const wrapper = getTimetrackingManagementWidgetInstance();

        await wrapper.findComponent(WidgetQueryDisplayer).trigger("click");

        expect(wrapper.findComponent(WidgetQueryDisplayer).exists()).toBeFalsy();
        expect(wrapper.findComponent(WidgetQueryEditor).exists()).toBeTruthy();
    });

    it("When the query is being edited, and the 'closeEditMode' event is emitted, then the query displayer should be displayed again but not query editor", async () => {
        const wrapper = getTimetrackingManagementWidgetInstance();

        await wrapper.findComponent(WidgetQueryDisplayer).trigger("click");

        expect(wrapper.findComponent(WidgetQueryDisplayer).exists()).toBeFalsy();
        expect(wrapper.findComponent(WidgetQueryEditor).exists()).toBeTruthy();

        await wrapper.findComponent(WidgetQueryEditor).vm.$emit("closeEditMode");

        expect(wrapper.findComponent(WidgetQueryDisplayer).exists()).toBeTruthy();
        expect(wrapper.findComponent(WidgetQueryEditor).exists()).toBeFalsy();
    });

    it("When the query is not being edited and the query has been modified, then the query save request should be displayed", async () => {
        const wrapper = getTimetrackingManagementWidgetInstance();

        await wrapper.findComponent(WidgetQueryDisplayer).trigger("click");
        await wrapper.findComponent(WidgetQueryEditor).vm.$emit("closeEditMode");

        expect(wrapper.findComponent(WidgetQueryEditor).exists()).toBeFalsy();
        expect(wrapper.findComponent(WidgetQueryDisplayer).exists()).toBeTruthy();
        expect(wrapper.findComponent(WidgetQuerySaveRequest).exists()).toBeTruthy();
    });
});
