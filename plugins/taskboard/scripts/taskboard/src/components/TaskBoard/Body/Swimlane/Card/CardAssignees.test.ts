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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CardAssignees from "./CardAssignees.vue";
import type { Card, Tracker, User } from "../../../../../type";
import UserAvatar from "./UserAvatar.vue";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";
import PeoplePicker from "./Editor/Assignees/PeoplePicker.vue";
import type { UserForPeoplePicker } from "../../../../../store/swimlane/card/UserForPeoplePicker";

jest.useFakeTimers();

function getCard(data: Partial<Card>): Card {
    return { assignees: [], is_in_edit_mode: true, ...data } as Card;
}

describe("CardAssignees", () => {
    let tracker: Tracker;
    const mock_load_possible_assignees = jest.fn();

    beforeEach(() => {
        tracker = { assigned_to_field: { is_multiple: false } } as Tracker;
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    function getWrapper(
        card: Card,
        users: UserForPeoplePicker[],
    ): VueWrapper<InstanceType<typeof CardAssignees>> {
        return shallowMount(CardAssignees, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        swimlane: {
                            getters: {
                                assignable_users: () => () => users,
                            },
                            actions: {
                                loadPossibleAssignees: mock_load_possible_assignees,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
            props: { card, tracker },
        });
    }

    it("displays an empty list", () => {
        const wrapper = getWrapper(getCard({ assignees: [] }), []);

        expect(wrapper.findComponent(UserAvatar).exists()).toBe(false);
    });

    it("displays the avatars of the card's assignees", () => {
        const steeve: User = {
            id: 101,
            display_name: "Steeve",
            avatar_url: "steeve.png",
        };
        const bob: User = {
            id: 102,
            display_name: "Bob",
            avatar_url: "Boob.png",
        };
        const wrapper = getWrapper(getCard({ assignees: [steeve, bob] }), []);

        const avatars = wrapper.findAllComponents(UserAvatar);
        expect(avatars).toHaveLength(2);
        expect(avatars.at(0)?.props("user")).toStrictEqual(steeve);
        expect(avatars.at(1)?.props("user")).toStrictEqual(bob);
    });

    it("switches the assignee to edit mode", async () => {
        mock_load_possible_assignees.mockResolvedValue([]);
        const wrapper = getWrapper(getCard({}), []);
        wrapper.get("[data-test=edit-assignees]").trigger("click");

        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=edit-assignees]").exists()).toBe(false);
        expect(wrapper.findComponent(PeoplePicker).exists()).toBe(true);
    });

    it("Displays an icon user-add if the current list is empty", () => {
        const wrapper = getWrapper(getCard({ assignees: [] }), []);

        const icon = wrapper.get("[data-test=icon]");
        expect(icon.classes()).toContain("taskboard-card-assignees-add-icon");
    });

    it("Displays an icon user-pencil if the current list is not empty", () => {
        const steeve: User = {
            id: 101,
            display_name: "Steeve",
            avatar_url: "steeve.png",
        };
        const wrapper = getWrapper(getCard({ assignees: [steeve] }), []);

        const icon = wrapper.get("[data-test=icon]");
        expect(icon.classes()).toContain("taskboard-card-assignees-edit-icon");
    });

    it("Loads users and displays a spinner when user clicks on them", async () => {
        const users = [{ id: 1, display_name: "Steeve" }] as UserForPeoplePicker[];
        const wrapper = getWrapper(getCard({}), users);

        wrapper.get("[data-test=edit-assignees]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=icon]").classes()).toContain(
            "taskboard-card-assignees-loading-icon",
        );
        expect(mock_load_possible_assignees).toHaveBeenCalledWith(expect.anything(), tracker);
    });

    it("When the field assigned_to is multiple, then aria-label is plural", () => {
        tracker = { assigned_to_field: { id: 1234, is_multiple: true } } as Tracker;
        const wrapper = getWrapper(getCard({}), []);

        expect(wrapper.get("[data-test=edit-assignees]").attributes("aria-label")).toBe(
            "Edit assignees",
        );
    });
});
