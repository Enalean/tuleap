/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import CreationModalPermissionsSection from "./CreationModalPermissionsSection.vue";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("CreationModalPermissionsSection", () => {
    let factory;

    beforeEach(() => {
        factory = (props) => {
            return shallowMount(CreationModalPermissionsSection, {
                props: { ...props },
                global: { ...getGlobalTestOptions({}) },
            });
        };
    });

    it("Show a spinner when the project ugroups are not yet loaded", () => {
        const wrapper = factory({
            project_ugroups: null,
            value: {
                can_read: [],
                can_write: [],
                can_manage: [],
            },
        });

        expect(wrapper.find(".document-permissions-creation-modal-title-spinner").exists()).toBe(
            true,
        );
        expect(
            wrapper.find("[data-test=document-creation-permissions-section-selector]").exists(),
        ).toBe(false);
    });

    it("Show the permissions selector when the project ugroups are loaded", () => {
        const wrapper = factory({
            project_ugroups: [{ id: "102_3", label: "Project members" }],
            value: {
                can_read: [],
                can_write: [],
                can_manage: [],
            },
        });

        expect(wrapper.find(".document-permissions-creation-modal-title-spinner").exists()).toBe(
            false,
        );
        expect(
            wrapper.find("[data-test=document-creation-permissions-section-selector]").exists(),
        ).toBe(true);
    });
});
