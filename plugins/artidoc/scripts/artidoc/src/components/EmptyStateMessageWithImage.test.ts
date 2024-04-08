/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { beforeAll, describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import ErrorMessageWithImage from "./EmptyStateMessageWithImage.vue";
import Tumbleweed from "@/assets/Tumbleweed.vue";
import type { ComponentPublicInstance } from "vue";

describe("EmptyStateMessageWithImage", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;

    beforeAll(() => {
        wrapper = shallowMount(ErrorMessageWithImage, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            slots: {
                "empty-state-image": Tumbleweed,
            },
            propsData: {
                title: "expected title",
                message: "expected message",
            },
        });
    });

    it("should display the error title", () => {
        expect(wrapper.find("section h1").text()).toContain("expected title");
    });
    it("should display the error message", () => {
        expect(wrapper.find("section p").text()).toContain("expected message");
    });
    it("should display the error image", () => {
        expect(wrapper.findComponent(Tumbleweed).exists()).toBe(true);
    });
});
