/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import { shallowMount } from "@vue/test-utils";
import LabelDecorator from "./LabelDecorator.vue";
import type { LabelDecorator as Decorator } from "@tuleap/plugin-tracker-rest-api-types";

describe("LabelDecorator", () => {
    it("should display the label and no icon, no link", () => {
        const decorator: Decorator = {
            label: "Title",
            description: "The title",
        };

        const wrapper = shallowMount(LabelDecorator, {
            props: {
                decorator,
            },
        });

        expect(wrapper.text()).toContain("Title");
        expect(wrapper.find("[data-test=icon]").exists()).toBe(false);
        expect(wrapper.find("[data-test=link]").exists()).toBe(false);
    });

    it("should display the label and no icon if icon is empty string", () => {
        const decorator: Decorator = {
            icon: "",
            label: "Title",
            description: "The title",
        };

        const wrapper = shallowMount(LabelDecorator, {
            props: {
                decorator,
            },
        });

        expect(wrapper.text()).toContain("Title");
        expect(wrapper.find("[data-test=icon]").exists()).toBe(false);
    });

    it("should display the label and no link if link is empty string", () => {
        const decorator: Decorator = {
            url: "",
            label: "Title",
            description: "The title",
        };

        const wrapper = shallowMount(LabelDecorator, {
            props: {
                decorator,
            },
        });

        expect(wrapper.text()).toContain("Title");
        expect(wrapper.find("[data-test=link]").exists()).toBe(false);
    });

    it("should display the label and the icon and no link", () => {
        const decorator: Decorator = {
            icon: "fa-bug",
            label: "Title",
            description: "The title",
        };

        const wrapper = shallowMount(LabelDecorator, {
            props: {
                decorator,
            },
        });

        expect(wrapper.text()).toContain("Title");
        expect(wrapper.find("[data-test=icon]").exists()).toBe(true);
        expect(wrapper.find("[data-test=link]").exists()).toBe(false);
    });

    it("should display the label and the icon and link", () => {
        const decorator: Decorator = {
            icon: "fa-bug",
            url: "https://example.com",
            label: "Title",
            description: "The title",
        };

        const wrapper = shallowMount(LabelDecorator, {
            props: {
                decorator,
            },
        });

        expect(wrapper.text()).toContain("Title");
        expect(wrapper.find("[data-test=icon]").exists()).toBe(true);
        expect(wrapper.find("[data-test=link]").exists()).toBe(true);
    });
});
