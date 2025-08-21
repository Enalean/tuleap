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

import { describe, expect, it, vi, beforeEach } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import WidgetQueryEditor from "./WidgetQueryEditor.vue";
import WidgetQueryDisplayer from "./WidgetQueryDisplayer.vue";
import TimetrackingManagementWidget from "./TimetrackingManagementWidget.vue";
import { QueryStub } from "../../tests/stubs/QueryStub";
import type { User } from "@tuleap/core-rest-api-types";
import * as rest_querier from "../api/rest-querier";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import process from "node:process";
import NoMoreViewableUsersWarning from "./NoMoreViewableUsersWarning.vue";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import ErrorMessage from "./ErrorMessage.vue";

describe("Given a Timetracking Management Widget", () => {
    function getTimetrackingManagementWidgetInstance(
        users_list: User[],
    ): VueWrapper<InstanceType<typeof TimetrackingManagementWidget>> {
        return shallowMount(TimetrackingManagementWidget, {
            props: {
                initial_query: QueryStub.withDefaults(users_list),
                widget_id: 42,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });
    }

    it("When the query displayer is clicked, then the query editor should be displayed but not query displayer", async () => {
        const wrapper = getTimetrackingManagementWidgetInstance([]);

        await wrapper.findComponent(WidgetQueryDisplayer).trigger("click");

        expect(wrapper.findComponent(WidgetQueryDisplayer).exists()).toBe(false);
        expect(wrapper.findComponent(WidgetQueryEditor).exists()).toBe(true);
    });

    it("When the query is being edited, and the editor wants to close, then the query displayer should be displayed again but not query editor", async () => {
        const wrapper = getTimetrackingManagementWidgetInstance([]);

        await wrapper.findComponent(WidgetQueryDisplayer).trigger("click");

        expect(wrapper.findComponent(WidgetQueryDisplayer).exists()).toBe(false);
        expect(wrapper.findComponent(WidgetQueryEditor).exists()).toBe(true);

        await wrapper.findComponent(WidgetQueryEditor).props("close")();

        expect(wrapper.findComponent(WidgetQueryDisplayer).exists()).toBe(true);
        expect(wrapper.findComponent(WidgetQueryEditor).exists()).toBe(false);
    });

    it("Should sort users", () => {
        const users: User[] = [
            {
                id: 1858,
                user_url: "/users/alice.hernandez",
                display_name: "Alice Hernandez (alice.hernandez)",
                avatar_url: "/avatar-ea78.png",
            },
            {
                id: 6871,
                user_url: "/users/bobby.arnold",
                display_name: "Bobby Arnold (bobby.arnold)",
                avatar_url: "/avatar-2129.png",
            },
            {
                id: 7964,
                user_url: "/users/alyssa.buchanan",
                display_name: "Alyssa Buchanan (alyssa.buchanan)",
                avatar_url: "/avatar-77a6.png",
            },
        ];

        const wrapper = getTimetrackingManagementWidgetInstance(users);

        expect(
            wrapper
                .findComponent(WidgetQueryDisplayer)
                .props("query")
                .users_list.map((user) => user.display_name),
        ).toStrictEqual([
            "Alice Hernandez (alice.hernandez)",
            "Alyssa Buchanan (alyssa.buchanan)",
            "Bobby Arnold (bobby.arnold)",
        ]);
    });

    it("should save the query and close the editor", async () => {
        const alice: User = {
            id: 1858,
            user_url: "/users/alice.hernandez",
            display_name: "Alice Hernandez (alice.hernandez)",
            avatar_url: "/avatar-ea78.png",
        };
        const bobby: User = {
            id: 6871,
            user_url: "/users/bobby.arnold",
            display_name: "Bobby Arnold (bobby.arnold)",
            avatar_url: "/avatar-2129.png",
        };
        const alyssa: User = {
            id: 7964,
            user_url: "/users/alyssa.buchanan",
            display_name: "Alyssa Buchanan (alyssa.buchanan)",
            avatar_url: "/avatar-77a6.png",
        };

        const wrapper = getTimetrackingManagementWidgetInstance([]);

        await wrapper.findComponent(WidgetQueryDisplayer).trigger("click");

        expect(wrapper.findComponent(WidgetQueryDisplayer).exists()).toBe(false);
        expect(wrapper.findComponent(WidgetQueryEditor).exists()).toBe(true);

        vi.spyOn(rest_querier, "putQuery").mockReturnValue(
            okAsync({
                viewable_users: [alice],
                no_more_viewable_users: [bobby],
            }),
        );

        wrapper.findComponent(WidgetQueryEditor).props("save")(
            QueryStub.withDefaults([alice, bobby, alyssa]),
        );

        await new Promise(process.nextTick);

        expect(
            wrapper
                .findComponent(WidgetQueryDisplayer)
                .props("query")
                .users_list.map((user) => user.display_name),
        ).toStrictEqual([alice.display_name]);
        expect(
            wrapper
                .findComponent(NoMoreViewableUsersWarning)
                .props("no_more_viewable_users")
                .map((user) => user.display_name),
        ).toStrictEqual([bobby.display_name]);

        expect(wrapper.findComponent(WidgetQueryDisplayer).exists()).toBe(true);
        expect(wrapper.findComponent(WidgetQueryEditor).exists()).toBe(false);
        expect(wrapper.findComponent(ErrorMessage).props("error_message")).toBe("");
    });

    describe("When the saving fails", () => {
        let wrapper: VueWrapper<InstanceType<typeof TimetrackingManagementWidget>>;

        beforeEach(async () => {
            wrapper = getTimetrackingManagementWidgetInstance([]);

            await wrapper.findComponent(WidgetQueryDisplayer).trigger("click");

            expect(wrapper.findComponent(WidgetQueryDisplayer).exists()).toBe(false);
            expect(wrapper.findComponent(WidgetQueryEditor).exists()).toBe(true);
            expect(wrapper.findComponent(ErrorMessage).props("error_message")).toBe("");

            vi.spyOn(rest_querier, "putQuery").mockReturnValue(
                errAsync(Fault.fromMessage("Bad request")),
            );

            wrapper.findComponent(WidgetQueryEditor).props("save")(QueryStub.withDefaults([]));

            await new Promise(process.nextTick);
        });

        it("Then the editor should stay and the error message should be displayed", () => {
            expect(wrapper.findComponent(WidgetQueryDisplayer).exists()).toBe(false);
            expect(wrapper.findComponent(WidgetQueryEditor).exists()).toBe(true);
            expect(wrapper.findComponent(ErrorMessage).props("error_message")).toBe(
                "Error while saving the query: Bad request",
            );
        });

        it("Then the error message is not displayed anymore if the user decides to close the editor", async () => {
            wrapper.findComponent(WidgetQueryEditor).props("close")();

            await new Promise(process.nextTick);

            expect(wrapper.findComponent(WidgetQueryDisplayer).exists()).toBe(true);
            expect(wrapper.findComponent(WidgetQueryEditor).exists()).toBe(false);
            expect(wrapper.findComponent(ErrorMessage).props("error_message")).toBe("");
        });
    });
});
