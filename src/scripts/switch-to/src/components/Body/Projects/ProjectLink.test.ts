/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import type { Project } from "../../../type";
import ProjectLink from "./ProjectLink.vue";
import { createTestingPinia } from "@pinia/testing";
import { useKeyboardNavigationStore } from "../../../stores/keyboard-navigation";
import type { KeyboardNavigationState, State } from "../../../stores/type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("ProjectLink", () => {
    it("Displays the link to a project", () => {
        const wrapper = shallowMount(ProjectLink, {
            props: {
                project: {
                    is_public: true,
                    project_name: "Guinea Pig",
                    project_uri: "/projects/gpig",
                    icon: "🐹",
                } as Project,
            },
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            are_restricted_users_allowed: true,
                        },
                    },
                })
            ),
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Changes the focus with arrow keys", async () => {
        const project = {
            is_public: true,
            project_name: "Guinea Pig",
            project_uri: "/pojects/gpig",
        } as Project;

        const wrapper = shallowMount(ProjectLink, {
            props: {
                project,
            },
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            are_restricted_users_allowed: true,
                        },
                    },
                })
            ),
        });

        const key = "ArrowUp";
        await wrapper.find("[data-test=project-link]").trigger("keydown", { key });

        expect(useKeyboardNavigationStore().changeFocusFromProject).toHaveBeenCalledWith({
            project,
            key,
        });
    });

    it("Forces the focus from the outside", async () => {
        const project = {
            is_public: true,
            project_name: "Guinea Pig",
            project_uri: "/pojects/gpig",
        } as Project;

        const wrapper = shallowMount(ProjectLink, {
            props: {
                project,
            },
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            are_restricted_users_allowed: true,
                        } as State,
                        "keyboard-navigation": {
                            programmatically_focused_element: null,
                        } as KeyboardNavigationState,
                    },
                })
            ),
        });

        const link = wrapper.find("[data-test=project-link]");
        if (!(link.element instanceof HTMLAnchorElement)) {
            throw Error("Unable to find the link");
        }

        const focus = jest.spyOn(link.element, "focus");

        await useKeyboardNavigationStore().$patch({ programmatically_focused_element: project });

        expect(focus).toHaveBeenCalled();
    });
});
