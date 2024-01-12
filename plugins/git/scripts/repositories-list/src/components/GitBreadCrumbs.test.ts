/*
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import GitBreadcrumbs from "./GitBreadcrumbs.vue";
import { setBreadcrumbSettings } from "../breadcrumb-presenter";
import { createLocalVueForTests } from "../helpers/local-vue-for-tests";

describe("GitBreadcrumbs", () => {
    it("displays breadcrumbs", async () => {
        setBreadcrumbSettings(
            "/admin/url",
            "/repositories/url",
            "/fork/url",
            "Guinea Pig",
            "/guinea-pig/url",
            {
                are_restricted_users_allowed: false,
                project_is_public_incl_restricted: false,
                project_is_private: false,
                project_is_public: true,
                project_is_private_incl_restricted: false,
                explanation_text: "Public",
                privacy_title: "Public",
            },
            [],
            "🐹",
        );

        const wrapper = shallowMount(GitBreadcrumbs, {
            localVue: await createLocalVueForTests(),
        });

        expect(wrapper).toMatchSnapshot();
    });
});
