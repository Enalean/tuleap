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

import { describe, it, expect, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import SidebarCollapseButton from "./SidebarCollapseButton.vue";
import { example_config } from "./project-sidebar-example-config";
import { ref } from "vue";
import * as strict_inject from "@tuleap/vue-strict-inject";

vi.mock("@tuleap/vue-strict-inject");

describe("SidebarCollapseButton", () => {
    it("displays sidebar collapse button when the user is logged in", () => {
        vi.spyOn(strict_inject, "strictInject").mockReturnValue(ref(example_config));

        const wrapper = shallowMount(SidebarCollapseButton, {
            props: {
                is_sidebar_collapsed: false,
                can_sidebar_be_collapsed: true,
            },
        });

        const button = wrapper.find("button");
        expect(button.exists()).toBe(true);
        expect(button.element.getAttribute("title")).toStrictEqual(
            example_config.internationalization.close_sidebar,
        );
        button.trigger("click");
        const click_events = wrapper.emitted("update:is_sidebar_collapsed");
        expect(click_events).toHaveLength(1);
        expect((click_events ?? [])[0]).toStrictEqual([true]);
    });

    it("does not display a button when the user is not logged in", () => {
        const config = example_config;
        config.user.is_logged_in = false;
        vi.spyOn(strict_inject, "strictInject").mockReturnValue(ref(config));
        const wrapper = shallowMount(SidebarCollapseButton, {
            props: {
                is_sidebar_collapsed: false,
                can_sidebar_be_collapsed: true,
            },
        });

        expect(wrapper.find("button").exists()).toBe(false);
    });

    it("does not display the collapse button when sidebar collapse behavior is disabled", () => {
        vi.spyOn(strict_inject, "strictInject").mockReturnValue(ref(example_config));
        const wrapper = shallowMount(SidebarCollapseButton, {
            props: {
                is_sidebar_collapsed: false,
                can_sidebar_be_collapsed: false,
            },
        });

        expect(wrapper.find("button").exists()).toBe(false);
    });
});
