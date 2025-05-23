/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import { shallowMount } from "@vue/test-utils";
import CriterionOwner from "./CriterionOwner.vue";
import type { Select2Plugin } from "tlp";
import * as autocomplete from "@tuleap/autocomplete-for-select2";
import * as retrieve_selected_owner from "../../../helpers/owner/retrieve-selected-owner";
import type { RestUser } from "../../../api/rest-querier";
import type { ConfigurationState } from "../../../store/configuration";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

vi.mock("@tuleap/autocomplete-for-select2", () => {
    return { autocomplete_users_for_select2: vi.fn() };
});

vi.useFakeTimers();

interface Select2Mock extends Select2Plugin {
    trigger(): this;

    on(): this;
}

describe("CriterionOwner", () => {
    it("should render the component with an already selected user", async () => {
        const autocompleter = vi.spyOn(autocomplete, "autocomplete_users_for_select2");
        let select2 = {} as Select2Mock;
        select2 = {
            trigger: (): Select2Mock => select2,
            on: (): Select2Mock => select2,
        } as unknown as Select2Mock;
        autocompleter.mockReturnValue(select2);

        const get_spy = vi.spyOn(retrieve_selected_owner, "retrieveSelectedOwner");
        const current_user = { display_name: "John Doe", username: "jdoe" } as RestUser;
        get_spy.mockResolvedValue(current_user);

        const wrapper = shallowMount(CriterionOwner, {
            props: {
                criterion: {
                    name: "owner",
                    label: "Owner",
                },
                value: "jdoe",
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: { project_name: "test" } as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
            },
        });
        await vi.runOnlyPendingTimersAsync();
        expect(wrapper.element).toMatchSnapshot();
        expect(wrapper.vm.get_currently_selected_user).toStrictEqual(current_user);
    });
});
