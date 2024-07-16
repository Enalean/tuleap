/**
 * Copyright (c) 2022-Present Enalean
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import LinkedProjects from "./LinkedProjects.vue";
import { example_config } from "../project-sidebar-example-config";
import * as tlp_popovers from "@tuleap/tlp-popovers";
import type { Popover } from "@tuleap/tlp-popovers";
import { ref } from "vue";
import type { Configuration } from "../configuration";
import { SIDEBAR_CONFIGURATION } from "../injection-symbols";

describe("LinkedProjects", () => {
    let is_sidebar_collapsed: boolean;
    beforeEach(() => {
        is_sidebar_collapsed = false;
    });

    function getWrapper(config: Configuration): VueWrapper {
        return shallowMount(LinkedProjects, {
            propsData: {
                is_sidebar_collapsed,
            },
            global: {
                provide: {
                    [SIDEBAR_CONFIGURATION.valueOf()]: ref(config),
                },
            },
        });
    }

    it("displays the linked projects with a popover", () => {
        const create_popover_spy = vi
            .spyOn(tlp_popovers, "createPopover")
            .mockReturnValue({} as Popover);

        const wrapper = getWrapper(example_config);

        expect(wrapper.element).toMatchSnapshot();
        expect(create_popover_spy).toHaveBeenCalled();
    });

    it("display nothing if there is no linked projects", () => {
        const config = {
            ...example_config,
            project: {
                ...example_config.project,
                linked_projects: null,
            },
        };
        const wrapper = getWrapper(config);
        expect(wrapper.element.textContent).toBe("");
    });

    it.each([
        [5, true, false],
        [6, false, true],
    ])(
        `Given the config does not specify the nb max project to display in sidebar, it defaults to 5.
     When nb projects = %s,
     Then projects in sidebar are displayed = %s
     And popover is displayed = %s`,
        (nb, expected_in_sidebar, expected_popover) => {
            vi.spyOn(tlp_popovers, "createPopover").mockReturnValue({} as Popover);

            const projects = Array(nb).fill({ name: "acme" });

            const config: Configuration = {
                ...example_config,
                project: {
                    ...example_config.project,
                    linked_projects: {
                        label: `${nb} aggregated projects`,
                        is_in_children_projects_context: true,
                        projects,
                    },
                },
            };

            const wrapper = getWrapper(config);

            expect(wrapper.find("[data-test=nav-bar-linked-projects]").exists()).toBe(
                expected_in_sidebar,
            );
            expect(
                wrapper
                    .find("[data-test=popover]")
                    .classes("project-sidebar-linked-projects-popover-nb-max-exceeded"),
            ).toBe(expected_popover);
        },
    );

    it.each([
        [3, true, false],
        [4, false, true],
    ])(
        `Given the config specifies the nb max project to display in sidebar is 3.
     When nb projects = %s,
     Then projects in sidebar are displayed = %s
     And popover is displayed = %s`,
        (nb, expected_in_sidebar, expected_popover) => {
            vi.spyOn(tlp_popovers, "createPopover").mockReturnValue({} as Popover);

            const projects = Array(nb).fill({ name: "acme" });

            const config = {
                ...example_config,
                project: {
                    ...example_config.project,
                    linked_projects: {
                        label: `${nb} aggregated projects`,
                        is_in_children_projects_context: true,
                        nb_max_projects_before_popover: 3,
                        projects,
                    },
                },
            };

            const wrapper = getWrapper(config);

            expect(wrapper.find("[data-test=nav-bar-linked-projects]").exists()).toBe(
                expected_in_sidebar,
            );
            expect(
                wrapper
                    .find("[data-test=popover]")
                    .classes("project-sidebar-linked-projects-popover-nb-max-exceeded"),
            ).toBe(expected_popover);
        },
    );

    it(`Given sidebar is collapsed
        Then accessible attributes are added to the popover anchor
        So that popover can be displayed with keyboard`, () => {
        vi.spyOn(tlp_popovers, "createPopover").mockReturnValue({} as Popover);
        is_sidebar_collapsed = true;
        const wrapper = getWrapper(example_config);

        expect(wrapper.find("[data-test=popover_anchor]").attributes("tabindex")).toBe("0");
        expect(wrapper.find("[data-test=popover_anchor]").attributes("role")).toBe("button");
    });

    it(`Given sidebar is not collapsed
        And projects are displayed in the sidebar
        Then accessible attributes are not added to the popover anchor
        Because we don't need to display a popover`, () => {
        vi.spyOn(tlp_popovers, "createPopover").mockReturnValue({} as Popover);

        const wrapper = getWrapper(example_config);
        expect(wrapper.find("[data-test=popover_anchor]").attributes("tabindex")).toBe("-1");
        expect(wrapper.find("[data-test=popover_anchor]").attributes("role")).toBe("");
    });

    it(`Given sidebar is not collapsed
        And projects are not displayed in the sidebar
        Then accessible attributes are added to the popover anchor
        So that popover can be displayed with keyboard`, () => {
        vi.spyOn(tlp_popovers, "createPopover").mockReturnValue({} as Popover);
        const config: Configuration = {
            ...example_config,
            project: {
                ...example_config.project,
                linked_projects: {
                    label: "20 aggregated projects",
                    is_in_children_projects_context: true,
                    projects: Array(20).fill({ name: "acme" }),
                },
            },
        };

        const wrapper = getWrapper(config);
        expect(wrapper.find("[data-test=popover_anchor]").attributes("tabindex")).toBe("0");
        expect(wrapper.find("[data-test=popover_anchor]").attributes("role")).toBe("button");
    });
});
