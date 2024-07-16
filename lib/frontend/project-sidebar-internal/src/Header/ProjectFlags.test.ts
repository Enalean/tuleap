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

import { describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ProjectFlags from "./ProjectFlags.vue";
import { example_config } from "../project-sidebar-example-config";
import { ref } from "vue";
import type { Popover } from "@tuleap/tlp-popovers";
import * as tlp_popovers from "@tuleap/tlp-popovers";
import { SIDEBAR_CONFIGURATION } from "../injection-symbols";
import type { Configuration } from "../configuration";

describe("ProjectFlags", () => {
    function getWrapper(config: Configuration): VueWrapper {
        return shallowMount(ProjectFlags, {
            global: {
                provide: {
                    [SIDEBAR_CONFIGURATION.valueOf()]: ref(config),
                },
            },
        });
    }

    it("displays the project flags with a popover", () => {
        const create_popover_spy = vi
            .spyOn(tlp_popovers, "createPopover")
            .mockReturnValue({} as Popover);

        const wrapper = getWrapper(example_config);

        expect(wrapper.element).toMatchSnapshot();
        expect(create_popover_spy).toHaveBeenCalled();
    });

    it("display nothing if there is no project flags", () => {
        const config = example_config;
        config.project.flags = [];

        const wrapper = getWrapper(config);

        expect(wrapper.element.textContent).toBe("");
    });
});
