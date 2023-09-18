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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import { default as ToolComponent } from "./ToolPresenter.vue";
import type { Tool } from "../configuration";
import PromotedItemPresenter from "./PromotedItemPresenter.vue";

describe("ToolPresenter", () => {
    it("displays an active link that do not force to open itself in a new tab", () => {
        const tool_data: Tool = {
            href: "/foo",
            label: "Label",
            description: "Description",
            icon: "fa-fw fa-solid fa-tlp-something",
            open_in_new_tab: false,
            is_active: true,
            shortcut_id: "plugin_something",
        };
        const wrapper = shallowMount(ToolComponent, {
            props: tool_data,
        });

        const anchor = wrapper.find("a");
        const anchor_element = anchor.element;

        expect(anchor.text()).toStrictEqual(tool_data.label);
        expect(anchor_element.getAttribute("href")).toStrictEqual(tool_data.href);
        expect(anchor_element.getAttribute("title")).toStrictEqual(tool_data.description);
        expect(anchor_element.getAttribute("data-shortcut-sidebar")).toBe(
            `sidebar-${tool_data.shortcut_id}`,
        );
        expect(anchor.find("[data-test=tool-icon]").element.className).toContain(tool_data.icon);
        expect(anchor_element.getAttribute("target")).toBe("_self");
        expect(anchor_element.classList.contains("active")).toBe(true);
        expect(anchor.find("[data-test=tool-new-tab-icon]").exists()).toBe(false);
    });

    it("displays a link that force itself to be opened in a new tab", () => {
        const tool_data: Tool = {
            href: "/foo",
            label: "Label",
            description: "Description",
            icon: "fa-fw fa-solid fa-tlp-somethingelse",
            open_in_new_tab: true,
            is_active: false,
            shortcut_id: "",
        };
        const wrapper = shallowMount(ToolComponent, {
            props: tool_data,
        });

        const anchor = wrapper.find("a");
        const anchor_element = anchor.element;

        expect(anchor.text()).toStrictEqual(tool_data.label);
        expect(anchor_element.getAttribute("href")).toStrictEqual(tool_data.href);
        expect(anchor_element.getAttribute("title")).toStrictEqual(tool_data.description);
        expect(anchor.find("[data-test=tool-icon]").element.className).toContain(tool_data.icon);
        expect(anchor_element.getAttribute("target")).toBe("_blank");
        expect(anchor_element.getAttribute("rel")).toBe("noopener noreferrer");
        expect(anchor_element.classList.contains("active")).toBe(false);
        expect(anchor.find("[data-test=tool-new-tab-icon]").exists()).toBe(true);
    });

    it("should displays a link that do not have any promoted items property at all for backward compatibility", () => {
        const tool_data: Tool = {
            href: "/foo",
            label: "Label",
            description: "Description",
            icon: "fa-fw fa-solid fa-tlp-somethingelse",
            open_in_new_tab: true,
            is_active: false,
            shortcut_id: "",
        };
        const wrapper = shallowMount(ToolComponent, {
            props: tool_data,
        });

        const anchor_element = wrapper.find("a").element;

        expect(
            anchor_element.classList.contains("project-sidebar-nav-item-with-promoted-items"),
        ).toBe(false);

        expect(wrapper.findComponent(PromotedItemPresenter).exists()).toBe(false);
    });

    it("should displays a link with an empty list of promoted items", () => {
        const tool_data: Tool = {
            href: "/foo",
            label: "Label",
            description: "Description",
            icon: "fa-fw fa-solid fa-tlp-somethingelse",
            open_in_new_tab: true,
            is_active: false,
            shortcut_id: "",
            promoted_items: [],
        };
        const wrapper = shallowMount(ToolComponent, {
            props: tool_data,
        });

        const anchor_element = wrapper.find("a").element;

        expect(
            anchor_element.classList.contains("project-sidebar-nav-item-with-promoted-items"),
        ).toBe(false);

        expect(wrapper.findComponent(PromotedItemPresenter).exists()).toBe(false);
    });

    it("should displays a link with a list of promoted items", () => {
        const tool_data: Tool = {
            href: "/foo",
            label: "Label",
            description: "Description",
            icon: "fa-fw fa-solid fa-tlp-somethingelse",
            open_in_new_tab: true,
            is_active: false,
            shortcut_id: "",
            promoted_items: [
                {
                    href: "/service/fake_git/fake_repo1",
                    label: "Repository 1",
                    description: "Awesome repository",
                },
                {
                    href: "/service/fake_git/fake_repo2",
                    label: "Repository 2",
                    description: "Another awesome repository",
                },
            ],
        };
        const wrapper = shallowMount(ToolComponent, {
            props: tool_data,
        });

        const anchor_element = wrapper.find("a").element;

        expect(
            anchor_element.classList.contains("project-sidebar-nav-item-with-promoted-items"),
        ).toBe(true);

        expect(wrapper.findAllComponents(PromotedItemPresenter)).toHaveLength(2);
    });
});
