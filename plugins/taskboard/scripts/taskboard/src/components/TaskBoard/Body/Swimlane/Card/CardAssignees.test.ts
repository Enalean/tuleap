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

import { shallowMount, Wrapper } from "@vue/test-utils";
import CardAssignees from "./CardAssignees.vue";
import { Card, Tracker, User } from "../../../../../type";
import UserAvatar from "./UserAvatar.vue";

function getWrapper(
    card: Card,
    tracker: Tracker = { assigned_to_field: null } as Tracker
): Wrapper<CardAssignees> {
    return shallowMount(CardAssignees, {
        propsData: {
            card,
            tracker
        }
    });
}

describe("CardAssignees", () => {
    it("displays an empty list", () => {
        const wrapper = getWrapper({ assignees: [] as User[] } as Card);

        expect(wrapper.classes()).toContain("taskboard-card-assignees");
        expect(wrapper.contains(UserAvatar)).toBe(false);
    });

    it("displays the avatars of the card's assignees", () => {
        const steeve: User = {
            id: 101,
            display_name: "Steeve",
            avatar_url: "steeve.png"
        };
        const bob: User = {
            id: 102,
            display_name: "Bob",
            avatar_url: "Boob.png"
        };
        const wrapper = getWrapper({ assignees: [steeve, bob] } as Card);

        const avatars = wrapper.findAll(UserAvatar);
        expect(avatars.length).toBe(2);
        expect(avatars.at(0).props("user")).toBe(steeve);
        expect(avatars.at(1).props("user")).toBe(bob);
    });

    it("switches the assignee to edit mode if the card is in edit mode", () => {
        const wrapper = getWrapper({ assignees: [] as User[], is_in_edit_mode: true } as Card);

        expect(wrapper.classes()).toContain("taskboard-card-assignees-edit-mode");
        expect(wrapper.classes()).not.toContain("taskboard-card-assignees-editable");
        expect(wrapper.contains("[data-test=icon]")).toBe(false);
    });

    it("adds additional class if assignees are editable", () => {
        const wrapper = getWrapper(
            { assignees: [] as User[], is_in_edit_mode: true } as Card,
            { assigned_to_field: { id: 123 } } as Tracker
        );

        expect(wrapper.classes()).toContain("taskboard-card-assignees-edit-mode");
        expect(wrapper.classes()).toContain("taskboard-card-assignees-editable");
        expect(wrapper.contains("[data-test=icon]")).toBe(true);
    });

    it("Displays an icon user-add if assignees are editable and the current list is empty", () => {
        const wrapper = getWrapper(
            { assignees: [] as User[], is_in_edit_mode: true } as Card,
            { assigned_to_field: { id: 123 } } as Tracker
        );

        const icon = wrapper.find("[data-test=icon]");
        expect(icon.classes()).toContain("fa");
        expect(icon.classes()).toContain("fa-user-plus");
        expect(icon.classes()).toContain("taskboard-card-assignees-add-icon");
    });

    it("Displays an icon user-pencil if assignees are editable and the current list is not empty", () => {
        const steeve: User = {
            id: 101,
            display_name: "Steeve",
            avatar_url: "steeve.png"
        };
        const wrapper = getWrapper(
            {
                assignees: [steeve],
                is_in_edit_mode: true
            } as Card,
            { assigned_to_field: { id: 123 } } as Tracker
        );

        const icon = wrapper.find("[data-test=icon]");
        expect(icon.classes()).toContain("fa");
        expect(icon.classes()).toContain("fa-tlp-user-pencil");
        expect(icon.classes()).toContain("taskboard-card-assignees-edit-icon");
    });
});
