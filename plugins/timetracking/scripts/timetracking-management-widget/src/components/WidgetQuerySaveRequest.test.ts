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

import { describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import WidgetQuerySaveRequest from "./WidgetQuerySaveRequest.vue";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import { RETRIEVE_QUERY, WIDGET_ID } from "../injection-symbols";
import type { Query } from "../query/QueryRetriever";
import { RetrieveQueryStub } from "../../tests/stubs/RetrieveQueryStub";

describe("Given a timetracking management widget query save request", () => {
    let query_retriever: Query;
    const widget_id = 49;

    function getWidgetQuerySaveRequestInstance(): VueWrapper {
        query_retriever = RetrieveQueryStub.withDefaults([]);

        return shallowMount(WidgetQuerySaveRequest, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [RETRIEVE_QUERY.valueOf()]: query_retriever,
                    [WIDGET_ID.valueOf()]: widget_id,
                },
            },
        });
    }

    it("When 'Save Query' button is clicked, then saveQuery should have been called", () => {
        const wrapper = getWidgetQuerySaveRequestInstance();

        const saveQuery = vi.spyOn(query_retriever, "saveQuery");

        wrapper.find("[data-test=save-button]").trigger("click");

        expect(saveQuery).toHaveBeenCalledWith(widget_id);
    });

    it("When 'Cancel' button is clicked, then saveQuery should not have been called", () => {
        const wrapper = getWidgetQuerySaveRequestInstance();

        const saveQuery = vi.spyOn(query_retriever, "saveQuery");

        wrapper.find("[data-test=cancel-button]").trigger("click");

        expect(saveQuery).not.toHaveBeenCalled();
    });
});
