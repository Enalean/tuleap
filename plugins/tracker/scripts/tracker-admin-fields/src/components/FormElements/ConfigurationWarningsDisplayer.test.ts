/*
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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
import { type VueWrapper, shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import { FIELDS_CONFIGURATION_WARNINGS } from "../../injection-symbols";
import ConfigurationWarningsDisplayer from "./ConfigurationWarningsDisplayer.vue";
import type { FieldConfigurationWarningsCollection } from "../../type";

const field_without_warning_id = 1001;
const field_with_warnings_id = 1002;

const warnings = {
    [field_with_warnings_id]: [
        { message: "Something is not configured properly" },
        {
            message: "Something is not configured properly at the following locations:",
            links: [
                { url: "https://www.example.com/", label: "Here" },
                { url: "https://www.example.com/", label: "There" },
            ],
        },
    ],
} as FieldConfigurationWarningsCollection;

describe("ConfigurationWarningsDisplayer", () => {
    const getWrapper = (field_id: number): VueWrapper =>
        shallowMount(ConfigurationWarningsDisplayer, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [FIELDS_CONFIGURATION_WARNINGS.valueOf()]: warnings,
                },
            },
            propsData: {
                field_id,
            },
        });

    it("When there are no warnings for a given field, Then it will display nothing.", () => {
        const wrapper = getWrapper(field_without_warning_id);

        expect(wrapper.find("[data-test=configuration-warnings]").exists()).toBe(false);
    });

    it("When there are warnings for a given field, Then it will display them", () => {
        const wrapper = getWrapper(field_with_warnings_id);

        expect(wrapper.find("[data-test=configuration-warnings]").exists()).toBe(true);

        const displayed_warnings = wrapper.findAll("[data-test=warning]");
        expect(displayed_warnings.length).toBe(warnings[field_with_warnings_id].length);

        const [simple_warnings, warning_with_links] = displayed_warnings;
        expect(simple_warnings.text()).toBe(warnings[field_with_warnings_id][0].message);

        const second_warning = warnings[field_with_warnings_id][1];
        expect(warning_with_links.text()).toBe(
            `${second_warning.message} ${second_warning.links[0].label}, ${second_warning.links[1].label}`,
        );

        const [first_link, second_link] =
            warning_with_links.findAll<HTMLAnchorElement>("[data-test=link]");
        expect(first_link.attributes("href")).toBe(second_warning.links[0].url);
        expect(first_link.text()).toBe(second_warning.links[0].label);
        expect(second_link.attributes("href")).toBe(second_warning.links[1].url);
        expect(second_link.text()).toBe(second_warning.links[1].label);
    });
});
