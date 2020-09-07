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

import { shallowMount } from "@vue/test-utils";
import BreadcrumbPrivacy from "./BreadcrumbPrivacy.vue";
import { ProjectPrivacy } from "../../project/privacy/project-privacy-helper";

describe("BreadcrumbPrivacy", () => {
    it("displays the project name, the privacy icon and a basic popover", () => {
        const wrapper = shallowMount(BreadcrumbPrivacy, {
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

        expect(wrapper).toMatchSnapshot();
    });

    it("displays project flags", () => {
        const wrapper = shallowMount(BreadcrumbPrivacy, {
            propsData: {
                project_flags: [
                    { label: "Confidentiel", description: "Description de confidentiel" },
                    { label: "Top secret", description: "Droit d'en connaitre ?" },
                ],
                privacy: {
                    are_restricted_users_allowed: false,
                    project_is_public: true,
                    explanation_text: "Some text",
                } as ProjectPrivacy,
                project_public_name: "ACME",
            },
        });

        expect(wrapper).toMatchSnapshot();
    });
});
