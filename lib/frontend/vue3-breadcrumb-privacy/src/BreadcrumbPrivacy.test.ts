/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import BreadcrumbPrivacy from "./BreadcrumbPrivacy.vue";
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";
import { getGlobalTestOptions } from "./global-options-for-test";

vi.mock("@tuleap/tlp-popovers", () => {
    return {
        createPopover: (): void => {
            //Do nothing
        },
    };
});

describe("BreadcrumbPrivacy", () => {
    it("displays the project name, the privacy icon and a basic popover", () => {
        const wrapper = shallowMount(BreadcrumbPrivacy, {
            global: {
                ...getGlobalTestOptions(),
            },
            propsData: {
                project_flags: [],
                privacy: {
                    are_restricted_users_allowed: false,
                    project_is_public: true,
                    explanation_text: "Some text",
                } as ProjectPrivacy,
                project_public_name: "ACME",
            },
        });

        expect(wrapper.vm.has_project_flags).toBeFalsy();
    });

    it("displays project flags", () => {
        const wrapper = shallowMount(BreadcrumbPrivacy, {
            global: {
                ...getGlobalTestOptions(),
            },
            propsData: {
                project_flags: [
                    { label: "Confidentiel", description: "Description de confidentiel" },
                    { label: "Confidentiel", description: "Droit d'en connaitre ?" },
                ],
                privacy: {
                    are_restricted_users_allowed: false,
                    project_is_public: true,
                    explanation_text: "Some text",
                } as ProjectPrivacy,
                project_public_name: "ACME",
            },
        });

        expect(wrapper.vm.has_project_flags).toBeTruthy();
        expect(wrapper.find("[data-test=project-flags]").text()).toContain("Confidentiel");
        expect(wrapper.find("[data-test=project-flags]").text()).toContain("Confidentiel");
    });
});
