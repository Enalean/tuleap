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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SidebarFooter from "./SidebarFooter.vue";
import { example_config } from "./project-sidebar-example-config";
import type { Configuration } from "./configuration";
import { SIDEBAR_CONFIGURATION } from "./injection-symbols";
import { ref } from "vue";

describe("SidebarFooter", () => {
    function getWrapper(config: Configuration): VueWrapper {
        return shallowMount(SidebarFooter, {
            global: {
                provide: {
                    [SIDEBAR_CONFIGURATION.valueOf()]: ref(config),
                },
            },
        });
    }

    it("displays a link with the version information without copyright", () => {
        const config: Configuration = {
            ...example_config,
            instance_information: {
                ...example_config.instance_information,
                copyright: null,
            },
        };

        const wrapper = getWrapper(config);

        expect(wrapper.find("a").exists()).toBe(true);
        expect(wrapper.find("[data-test=copyright]").exists()).toBe(false);
        expect(wrapper.text()).toContain("Dev Build 13.2.99.999");
    });

    it("can display copyright information", () => {
        const expected_copyright = "My Copyright Notice";
        const config: Configuration = {
            ...example_config,
            instance_information: {
                ...example_config.instance_information,
                copyright: expected_copyright,
            },
        };

        const wrapper = getWrapper(config);

        expect(wrapper.find("[data-test=copyright]").exists()).toBe(true);
        expect(wrapper.text()).toContain(expected_copyright);
    });
});
