/**
 * Copyright (c) 2021-Present Enalean
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

import { describe, it, expect } from "vitest";
import ProjectSidebar from "./ProjectSidebar.vue";
import { shallowMount } from "@vue/test-utils";
import { example_config } from "./project-sidebar-example-config";
import SidebarCollapseButton from "./SidebarCollapseButton.vue";

describe("ProjectSidebar", () => {
    it("displays sidebar", () => {
        const wrapper = shallowMount(ProjectSidebar, {
            props: { config: JSON.stringify(example_config) },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays nothing when config attribute is incorrect", () => {
        const wrapper = shallowMount(ProjectSidebar, { props: { config: "" } });

        expect(wrapper.element.childNodes).toHaveLength(0);
    });

    it.each([
        [false, false],
        [true, true],
    ])(
        `Given configuration does not contain information about is_collapsible,
        And collapsed attribute is %s
        Then sidebar can be collapsed and is currently collapsed = %s`,
        (collapsed, expected) => {
            // eslint-disable-next-line @typescript-eslint/no-unused-vars
            const { is_collapsible, ...config_without_is_collapsible } = example_config;

            const wrapper = shallowMount(ProjectSidebar, {
                props: { config: JSON.stringify(config_without_is_collapsible), collapsed },
            });

            expect(wrapper.element.classList.contains("sidebar-collapsed")).toBe(expected);
            expect(wrapper.findComponent(SidebarCollapseButton).props("is_sidebar_collapsed")).toBe(
                expected,
            );
            expect(
                wrapper.findComponent(SidebarCollapseButton).props("can_sidebar_be_collapsed"),
            ).toBe(true);
        },
    );

    it.each([
        [false, false],
        [true, true],
    ])(
        `Given configuration have is_collapsible = true,
        And collapsed attribute is %s
        Then sidebar can be collapsed and is currently collapsed = %s`,
        (collapsed, expected) => {
            const wrapper = shallowMount(ProjectSidebar, {
                props: {
                    config: JSON.stringify({ ...example_config, is_collapsible: true }),
                    collapsed,
                },
            });

            expect(wrapper.element.classList.contains("sidebar-collapsed")).toBe(expected);
            expect(wrapper.findComponent(SidebarCollapseButton).props("is_sidebar_collapsed")).toBe(
                expected,
            );
            expect(
                wrapper.findComponent(SidebarCollapseButton).props("can_sidebar_be_collapsed"),
            ).toBe(true);
        },
    );

    it.each([[false], [true]])(
        `Given configuration have is_collapsible = false,
        And collapsed attribute is %s
        Then sidebar cannot be collapsed and is currently not collapsed`,
        (collapsed) => {
            const wrapper = shallowMount(ProjectSidebar, {
                props: {
                    config: JSON.stringify({ ...example_config, is_collapsible: false }),
                    collapsed,
                },
            });

            expect(wrapper.element.classList.contains("sidebar-collapsed")).toBe(false);
            expect(wrapper.findComponent(SidebarCollapseButton).props("is_sidebar_collapsed")).toBe(
                false,
            );
            expect(
                wrapper.findComponent(SidebarCollapseButton).props("can_sidebar_be_collapsed"),
            ).toBe(false);
        },
    );
});
