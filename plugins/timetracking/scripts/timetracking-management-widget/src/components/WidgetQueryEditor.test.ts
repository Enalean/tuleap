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

import { describe, it, expect, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import WidgetQueryEditor from "./WidgetQueryEditor.vue";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import { PredefinedTimePeriodsVueStub } from "../../tests/stubs/PredefinedTimePeriodsVueStub";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { LazyboxVueStub } from "../../tests/stubs/LazyboxVueStub";
import type { User } from "@tuleap/core-rest-api-types";
import type { Query } from "../query/QueryRetriever";
import { RetrieveQueryStub } from "../../tests/stubs/RetrieveQueryStub";

vi.mock("tlp", () => ({
    datePicker: (): { setDate(): void } => ({
        setDate: (): void => {
            // Do nothing
        },
    }),
}));

const mireillelabeille: User = {
    id: 101,
    avatar_url: "https://example.com/users/mireillelabeille/avatar-mireillelabeille.png",
    display_name: "Mireille L'Abeille (mireillelabeille)",
    user_url: "/users/mireillelabeille",
};

const bellelacoccinelle: User = {
    id: 102,
    avatar_url: "https://example.com/users/bellelacoccinelle/avatar-bellelacoccinelle.png",
    display_name: "Belle La Coccinelle (bellelacoccinelle)",
    user_url: "/users/bellelacoccinelle",
};

const new_start_date = "2024-06-01";
const new_end_date = "2024-06-12";

describe("Given a timetracking management widget query editor", () => {
    let users_list: User[] = [];
    let query_retriever: Query;

    function getWidgetQueryEditorInstance(): VueWrapper {
        query_retriever = RetrieveQueryStub.withDefaults(users_list);
        vi.spyOn(strict_inject, "strictInject").mockReturnValue(query_retriever);

        return shallowMount(WidgetQueryEditor, {
            global: {
                ...getGlobalTestOptions(),
                stubs: {
                    "tuleap-predefined-time-period-select": PredefinedTimePeriodsVueStub,
                    "tuleap-lazybox": LazyboxVueStub,
                },
            },
        });
    }

    it("When the submit button is clicked, setQuery should be called with the required arguments and the edition mode is closed", () => {
        users_list = [mireillelabeille, bellelacoccinelle];

        const wrapper = getWidgetQueryEditorInstance();
        const start_date_input = wrapper.find<HTMLInputElement>("[data-test=start-date-input]");
        const end_date_input = wrapper.find<HTMLInputElement>("[data-test=end-date-input]");

        const setQuery = vi.spyOn(query_retriever, "setQuery");

        start_date_input.setValue(new_start_date);
        end_date_input.setValue(new_end_date);
        wrapper.find("[data-test=search-button]").trigger("click");

        const close_edit_mode_event = wrapper.emitted("closeEditMode");

        expect(setQuery).toHaveBeenCalledWith(new_start_date, new_end_date, "", users_list);
        expect(close_edit_mode_event).toBeDefined();
    });

    it("When the cancel button is clicked, setQuery should not be called and the edition mode is closed", () => {
        const wrapper = getWidgetQueryEditorInstance();

        wrapper.find("[data-test=cancel-button]").trigger("click");

        const setQuery = vi.spyOn(query_retriever, "setQuery");
        const close_edit_mode_event = wrapper.emitted("closeEditMode");

        expect(setQuery).toBeCalledTimes(0);
        expect(close_edit_mode_event).toBeDefined();
    });

    it("When start date is selected manually, then the selected predefined time period should be cleared", async () => {
        const wrapper = getWidgetQueryEditorInstance();
        const predefined_time_period_stub = wrapper.findComponent(PredefinedTimePeriodsVueStub);

        const input = wrapper.find<HTMLInputElement>("[data-test=start-date-input]");
        await input.setValue("2024-23-05");

        expect(predefined_time_period_stub.vm.getCurrentlySelectedPredefinedTimePeriod()).toBe("");
    });

    it("When end date is selected manually, then the selected predefined time period should be cleared", async () => {
        const wrapper = getWidgetQueryEditorInstance();

        const predefined_time_period_stub = wrapper.findComponent(PredefinedTimePeriodsVueStub);

        const input = wrapper.find<HTMLInputElement>("[data-test=end-date-input]");
        await input.setValue("2024-23-05");

        expect(predefined_time_period_stub.vm.getCurrentlySelectedPredefinedTimePeriod()).toBe("");
    });

    it(`When some users are already selected, then lazybox's selection should be set with these users`, () => {
        users_list = [mireillelabeille, bellelacoccinelle];

        const wrapper = getWidgetQueryEditorInstance();

        const lazybox_stub = wrapper.findComponent(LazyboxVueStub);

        const selection = lazybox_stub.vm.getInitialSelection().map((item) => {
            const user = item.value as User;
            return user.id;
        });

        expect(selection).toStrictEqual([mireillelabeille.id, bellelacoccinelle.id]);
    });

    it(`When no users are selected yet, then lazybox's selection should not be set`, () => {
        users_list = [];
        const wrapper = getWidgetQueryEditorInstance();

        const lazybox_stub = wrapper.findComponent(LazyboxVueStub);

        expect(lazybox_stub.vm.getInitialSelection()).toStrictEqual([]);
    });
});
