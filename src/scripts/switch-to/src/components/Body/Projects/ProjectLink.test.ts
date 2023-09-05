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
import type { KeyboardNavigationState } from "../../../stores/type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { ARE_RESTRICTED_USERS_ALLOWED } from "../../../injection-keys";

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
                location: window.location,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [ARE_RESTRICTED_USERS_ALLOWED as symbol]: true,
                },
            },
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
                location: window.location,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [ARE_RESTRICTED_USERS_ALLOWED as symbol]: true,
                },
            },
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
                location: window.location,
            },
            global: {
                ...getGlobalTestOptions(
                    createTestingPinia({
                        initialState: {
                            "keyboard-navigation": {
                                programmatically_focused_element: null,
                            } as KeyboardNavigationState,
                        },
                    }),
                ),
                provide: {
                    [ARE_RESTRICTED_USERS_ALLOWED as symbol]: true,
                },
            },
        });

        const link = wrapper.find("[data-test=project-link]");
        if (!(link.element instanceof HTMLAnchorElement)) {
            throw Error("Unable to find the link");
        }

        const focus = jest.spyOn(link.element, "focus");

        await useKeyboardNavigationStore().$patch({ programmatically_focused_element: project });

        expect(focus).toHaveBeenCalled();
    });

    it("should go to the project when I click on the container", async () => {
        const location = { ...window.location, assign: jest.fn() };

        const wrapper = shallowMount(ProjectLink, {
            props: {
                project: {
                    is_public: true,
                    project_name: "Guinea Pig",
                    project_uri: "/projects/gpig",
                    icon: "🐹",
                } as Project,
                location,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [ARE_RESTRICTED_USERS_ALLOWED as symbol]: true,
                },
            },
        });

        await wrapper.find("[data-test=switch-to-projects-project]").trigger("click");
        expect(location.assign).toHaveBeenCalled();
    });

    it("should not manually assign the location when the real link is clicked", async () => {
        const location = { ...window.location, assign: jest.fn() };

        const wrapper = shallowMount(ProjectLink, {
            props: {
                project: {
                    is_public: true,
                    project_name: "Guinea Pig",
                    project_uri: "/projects/gpig",
                    icon: "🐹",
                } as Project,
                location,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [ARE_RESTRICTED_USERS_ALLOWED as symbol]: true,
                },
            },
        });

        await wrapper.find("[data-test=project-link]").trigger("click");
        expect(location.assign).not.toHaveBeenCalled();
    });
});
