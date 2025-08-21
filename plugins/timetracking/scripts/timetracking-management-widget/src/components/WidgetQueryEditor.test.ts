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

import type { Mock } from "vitest";
import { describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import WidgetQueryEditor from "./WidgetQueryEditor.vue";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import { PredefinedTimePeriodsVueStub } from "../../tests/stubs/PredefinedTimePeriodsVueStub";
import { LazyboxVueStub } from "../../tests/stubs/LazyboxVueStub";
import type { User } from "@tuleap/core-rest-api-types";
import { QueryStub } from "../../tests/stubs/QueryStub";
import { USER_LOCALE_KEY } from "../injection-symbols";
import type { Query } from "../type";

vi.mock("@tuleap/tlp-date-picker", () => ({
    datePicker(): { setDate(): void } {
        return {
            setDate(): void {
                // Do nothing
            },
        };
    },
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
    let is_query_being_saved = false;
    let query: Query;
    let save: Mock;
    let close: Mock;

    function getWidgetQueryEditorInstance(): VueWrapper {
        query = QueryStub.withDefaults(users_list);
        save = vi.fn();
        close = vi.fn();

        return shallowMount(WidgetQueryEditor, {
            props: {
                query,
                save,
                close,
                is_query_being_saved,
            },
            global: {
                ...getGlobalTestOptions(),
                stubs: {
                    "tuleap-predefined-time-period-select": PredefinedTimePeriodsVueStub,
                    "tuleap-lazybox": LazyboxVueStub,
                },
                provide: {
                    [USER_LOCALE_KEY.valueOf()]: "en_US",
                },
            },
        });
    }

    it("When the submit button is clicked, setQuery should be called with the required arguments and the edition mode is closed", () => {
        users_list = [mireillelabeille, bellelacoccinelle];

        const wrapper = getWidgetQueryEditorInstance();
        const start_date_input = wrapper.find<HTMLInputElement>("[data-test=start-date-input]");
        const end_date_input = wrapper.find<HTMLInputElement>("[data-test=end-date-input]");

        start_date_input.setValue(new_start_date);
        end_date_input.setValue(new_end_date);
        wrapper.find("[data-test=save-button]").trigger("click");

        expect(save).toHaveBeenCalledWith({
            start_date: new_start_date,
            end_date: new_end_date,
            predefined_time_period: "",
            users_list,
        });
        expect(close).not.toHaveBeenCalled();
    });

    it("When the cancel button is clicked, setQuery should not be called and the edition mode is closed", () => {
        const wrapper = getWidgetQueryEditorInstance();

        wrapper.find("[data-test=cancel-button]").trigger("click");

        expect(save).not.toHaveBeenCalled();
        expect(close).toHaveBeenCalled();
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

    it(`When the query is being saved, then save button is disabled`, () => {
        is_query_being_saved = true;
        const wrapper = getWidgetQueryEditorInstance();

        expect(wrapper.find("[data-test=save-button]").attributes("disabled")).toBeDefined();
        expect(wrapper.find("[data-test=cancel-button]").attributes("disabled")).toBeDefined();
    });
});
