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

import { describe, it, expect, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import PrivacyBadge from "./PrivacyBadge.vue";
import { example_config } from "../project-sidebar-example-config";
import { ref } from "vue";
import * as tlp_popovers from "@tuleap/tlp-popovers";
import type { Popover } from "@tuleap/tlp-popovers";
import * as strict_inject from "@tuleap/vue-strict-inject";

vi.mock("@tuleap/vue-strict-inject");

describe("PrivacyBadge", () => {
    it("displays the badge with its popover", () => {
        const create_popover_spy = vi
            .spyOn(tlp_popovers, "createPopover")
            .mockReturnValue({} as Popover);

        vi.spyOn(strict_inject, "strictInject").mockReturnValue(ref(example_config));

        const wrapper = shallowMount(PrivacyBadge);

        expect(wrapper.element).toMatchSnapshot();
        expect(create_popover_spy).toHaveBeenCalled();
    });
});
