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

import SidebarLogo from "./SidebarLogo.vue";
import { shallowMount } from "@vue/test-utils";
import { example_config } from "./project-sidebar-example-config";
import { SIDEBAR_CONFIGURATION } from "./injection-symbols";
import { ref } from "vue";
import type { Configuration } from "./configuration";

describe("SidebarLogo", () => {
    it("displays default logo when no customization has been done", () => {
        const wrapper = shallowMount(SidebarLogo, {
            global: {
                provide: {
                    [SIDEBAR_CONFIGURATION.valueOf()]: ref(example_config),
                },
            },
        });

        const logo_link = wrapper.find("a");
        const logo_link_element = logo_link.element;
        expect(logo_link_element.href).toStrictEqual(
            example_config.instance_information.logo.logo_link_href
        );
        expect(logo_link.find("[data-test=default-normal-logo]").exists()).toBe(true);
        expect(logo_link.find("[data-test=default-small-logo]").exists()).toBe(true);
    });

    it("displays custom SVG logos", () => {
        const config: Configuration = {
            ...example_config,
            instance_information: {
                ...example_config.instance_information,
                logo: {
                    logo_link_href: "https://example.com/",
                    svg: {
                        small: "<svg>small</svg>",
                        normal: "<svg>normal</svg>",
                    },
                },
            },
        };
        const wrapper = shallowMount(SidebarLogo, {
            global: {
                provide: {
                    [SIDEBAR_CONFIGURATION.valueOf()]: ref(config),
                },
            },
        });

        const logo_link = wrapper.find("a");
        const logo_link_element = logo_link.element;
        expect(logo_link_element.href).toStrictEqual(
            config.instance_information.logo.logo_link_href
        );
        expect(logo_link.find("[data-test=custom-normal-logo]").element.innerHTML).toStrictEqual(
            config.instance_information.logo.svg?.normal
        );
        expect(logo_link.find("[data-test=custom-small-logo]").element.innerHTML).toStrictEqual(
            config.instance_information.logo.svg?.small
        );
    });
});
