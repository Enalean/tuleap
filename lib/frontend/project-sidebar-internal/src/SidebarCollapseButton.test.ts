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

import { beforeEach, describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SidebarCollapseButton from "./SidebarCollapseButton.vue";
import { example_config } from "./project-sidebar-example-config";
import { SIDEBAR_CONFIGURATION } from "./injection-symbols";
import type { Configuration } from "./configuration";
import { ref } from "vue";

describe("SidebarCollapseButton", () => {
    let config: Configuration;
    beforeEach(() => {
        config = example_config;
    });

    function getWrapper(can_be_collapsed: boolean): VueWrapper {
        return shallowMount(SidebarCollapseButton, {
            props: {
                is_sidebar_collapsed: false,
                can_sidebar_be_collapsed: can_be_collapsed,
            },
            global: {
                provide: {
                    [SIDEBAR_CONFIGURATION.valueOf()]: ref(config),
                },
            },
        });
    }

    it("displays sidebar collapse button when the user is logged in", () => {
        const wrapper = getWrapper(true);

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
        config.user.is_logged_in = false;
        const wrapper = getWrapper(true);

        expect(wrapper.find("button").exists()).toBe(false);
    });

    it("does not display the collapse button when sidebar collapse behavior is disabled", () => {
        const wrapper = getWrapper(false);

        expect(wrapper.find("button").exists()).toBe(false);
    });
});
