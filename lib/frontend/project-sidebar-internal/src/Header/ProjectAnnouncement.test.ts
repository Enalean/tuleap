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
import ProjectAnnouncement from "./ProjectAnnouncement.vue";
import { SIDEBAR_CONFIGURATION, TRIGGER_SHOW_PROJECT_ANNOUNCEMENT } from "../injection-symbols";
import { example_config } from "../project-sidebar-example-config";
import { ref } from "vue";
import * as strict_inject from "@tuleap/vue-strict-inject";

vi.mock("@tuleap/vue-strict-inject");

describe("ProjectAnnouncement", () => {
    it("displays show project announcement button", () => {
        const trigger_announcement = vi.fn();

        vi.spyOn(strict_inject, "strictInject").mockImplementation((key) => {
            switch (key) {
                case SIDEBAR_CONFIGURATION:
                    return ref(example_config);
                case TRIGGER_SHOW_PROJECT_ANNOUNCEMENT:
                    return trigger_announcement;
            }
        });

        const wrapper = shallowMount(ProjectAnnouncement);

        const trigger_button = wrapper.find("button");
        expect(trigger_button.exists()).toBe(true);
        trigger_button.trigger("click");
        expect(trigger_announcement).toHaveBeenCalled();
    });

    it("display nothing if there is no project announcement", () => {
        const config = example_config;
        config.project.has_project_announcement = false;

        vi.spyOn(strict_inject, "strictInject").mockImplementation((key) => {
            switch (key) {
                case SIDEBAR_CONFIGURATION:
                    return ref(config);
                case TRIGGER_SHOW_PROJECT_ANNOUNCEMENT:
                    return vi.fn();
            }
        });

        const wrapper = shallowMount(ProjectAnnouncement);

        expect(wrapper.find("button").exists()).toBe(false);
    });
});
