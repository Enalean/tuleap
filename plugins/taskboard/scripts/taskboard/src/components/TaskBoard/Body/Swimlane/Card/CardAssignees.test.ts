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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CardAssignees from "./CardAssignees.vue";
import type { Card, Tracker, User } from "../../../../../type";
import UserAvatar from "./UserAvatar.vue";
import { createTaskboardLocalVue } from "../../../../../helpers/local-vue-for-test";
import PeoplePicker from "./Editor/Assignees/PeoplePicker.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { RootState } from "../../../../../store/type";
import type { UserForPeoplePicker } from "../../../../../store/swimlane/card/type";

jest.mock("tlp");
jest.useFakeTimers();

async function getWrapper(
    card: Card,
    tracker: Tracker = { assigned_to_field: null } as Tracker
): Promise<Wrapper<CardAssignees>> {
    return shallowMount(CardAssignees, {
        localVue: await createTaskboardLocalVue(),
        mocks: {
            $store: createStoreMock({
                state: { swimlane: {} } as RootState,
            }),
            getters: {
                "swimlane/assignable_users": (): UserForPeoplePicker[] => [],
            },
        },
        propsData: {
            card,
            tracker,
            value: [] as number[],
        },
    });
}

describe("CardAssignees", () => {
    it("displays an empty list", async () => {
        const wrapper = await getWrapper({ assignees: [] as User[] } as Card);

        expect(wrapper.classes()).toContain("taskboard-card-assignees");
        expect(wrapper.findComponent(UserAvatar).exists()).toBe(false);
    });

    it("displays the avatars of the card's assignees", async () => {
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
        const wrapper = await getWrapper({ assignees: [steeve, bob] } as Card);

        const avatars = wrapper.findAllComponents(UserAvatar);
        expect(avatars).toHaveLength(2);
        expect(avatars.at(0).props("user")).toBe(steeve);
        expect(avatars.at(1).props("user")).toBe(bob);
    });

    it("switches the assignee to edit mode if the card is in edit mode", async () => {
        const wrapper = await getWrapper({
            assignees: [] as User[],
            is_in_edit_mode: true,
        } as Card);

        expect(wrapper.classes()).toContain("taskboard-card-edit-mode-assignees");
        expect(wrapper.classes()).not.toContain("taskboard-card-assignees-editable");
        expect(wrapper.classes()).not.toContain("taskboard-card-assignees-edit-mode");
        expect(wrapper.find("[data-test=icon]").exists()).toBe(false);
    });

    it("adds additional class if assignees are editable", async () => {
        const wrapper = await getWrapper(
            { assignees: [] as User[], is_in_edit_mode: true } as Card,
            { assigned_to_field: { id: 123 } } as Tracker
        );

        expect(wrapper.classes()).toContain("taskboard-card-edit-mode-assignees");
        expect(wrapper.classes()).toContain("taskboard-card-assignees-editable");
        expect(wrapper.classes()).not.toContain("taskboard-card-assignees-edit-mode");
        expect(wrapper.find("[data-test=icon]").exists()).toBe(true);
    });

    it("Displays an icon user-add if assignees are editable and the current list is empty", async () => {
        const wrapper = await getWrapper(
            { assignees: [] as User[], is_in_edit_mode: true } as Card,
            { assigned_to_field: { id: 123 } } as Tracker
        );

        const icon = wrapper.get("[data-test=icon]");
        expect(icon.classes()).toContain("fa");
        expect(icon.classes()).toContain("fa-user-plus");
        expect(icon.classes()).toContain("taskboard-card-assignees-add-icon");
    });

    it("Displays an icon user-pencil if assignees are editable and the current list is not empty", async () => {
        const steeve: User = {
            id: 101,
            display_name: "Steeve",
            avatar_url: "steeve.png",
        };
        const wrapper = await getWrapper(
            {
                assignees: [steeve],
                is_in_edit_mode: true,
            } as Card,
            { assigned_to_field: { id: 123 } } as Tracker
        );

        const icon = wrapper.get("[data-test=icon]");
        expect(icon.classes()).toContain("fa");
        expect(icon.classes()).toContain("fa-tlp-user-pencil");
        expect(icon.classes()).toContain("taskboard-card-assignees-edit-icon");
    });

    it("Loads users and displays a spinner when assignees are editable and user clicks on them", async () => {
        const tracker = { assigned_to_field: { id: 123 } } as Tracker;
        const wrapper = await getWrapper(
            {
                assignees: [] as User[],
                is_in_edit_mode: true,
            } as Card,
            tracker
        );
        wrapper.vm.$store.getters["swimlane/assignable_users"] = (): UserForPeoplePicker[] =>
            [{ id: 1, display_name: "Steeve" }] as UserForPeoplePicker[];

        wrapper.trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith(
            "swimlane/loadPossibleAssignees",
            tracker
        );
        expect(wrapper.classes()).toContain("taskboard-card-edit-mode-assignees");
        expect(wrapper.classes()).toContain("taskboard-card-assignees-editable");
        expect(wrapper.classes()).not.toContain("taskboard-card-assignees-edit-mode");
        expect(wrapper.findComponent(PeoplePicker).exists()).toBe(false);

        const icon = wrapper.get("[data-test=icon]");
        expect(icon.classes()).toContain("fa");
        expect(icon.classes()).toContain("fa-circle-o-notch");
        expect(icon.classes()).toContain("fa-spin");
        expect(icon.classes()).toContain("taskboard-card-assignees-loading-icon");
    });

    it("Does not switch the assignees to edit mode if the card is not in edit mode and user click on them", async () => {
        const wrapper = await getWrapper(
            {
                assignees: [] as User[],
                is_in_edit_mode: false,
            } as Card,
            { assigned_to_field: { id: 123 } } as Tracker
        );

        wrapper.trigger("click");

        expect(wrapper.classes()).toContain("taskboard-card-assignees");
        expect(wrapper.classes()).not.toContain("taskboard-card-edit-mode-assignees");
        expect(wrapper.classes()).not.toContain("taskboard-card-assignees-editable");
        expect(wrapper.classes()).not.toContain("taskboard-card-assignees-edit-mode");
        expect(wrapper.find("[data-test=icon]").exists()).toBe(false);
    });

    describe("role/tabindex/aria-label", () => {
        describe("When the card is in edit mode and assignees are updatable", () => {
            let card: Card, tracker: Tracker, wrapper: Wrapper<CardAssignees>;

            beforeEach(async () => {
                card = { assignees: [] as User[], is_in_edit_mode: true } as Card;
                tracker = { assigned_to_field: { id: 1234, is_multiple: false } } as Tracker;

                wrapper = await getWrapper(card, tracker);
            });

            it("is a button", () => expect(wrapper.attributes("role")).toBe("button"));
            it("is focusable", () => expect(wrapper.attributes("tabindex")).toBe("0"));
            it("has an aria label", () =>
                expect(wrapper.attributes("aria-label")).toBe("Edit assignee"));
        });

        describe("When the field assigned_to is multiple, then aria-label is plural", () => {
            let card: Card, tracker: Tracker, wrapper: Wrapper<CardAssignees>;

            beforeEach(async () => {
                card = { assignees: [] as User[], is_in_edit_mode: true } as Card;
                tracker = { assigned_to_field: { id: 1234, is_multiple: true } } as Tracker;

                wrapper = await getWrapper(card, tracker);
            });

            it("has an aria label", () =>
                expect(wrapper.attributes("aria-label")).toBe("Edit assignees"));
        });

        describe("When the card is not in edit mode or assignees are not updatable", () => {
            let card: Card, tracker: Tracker, wrapper: Wrapper<CardAssignees>;

            beforeEach(async () => {
                card = { assignees: [] as User[], is_in_edit_mode: false } as Card;
                tracker = { assigned_to_field: null } as Tracker;

                wrapper = await getWrapper(card, tracker);
            });

            it("is not a button", () => expect(wrapper.attributes("role")).toBe(""));
            it("is not focusable", () => expect(wrapper.attributes("tabindex")).toBe("-1"));
            it("has no aria label", () => expect(wrapper.attributes("aria-label")).toBe(""));
        });
    });
});
