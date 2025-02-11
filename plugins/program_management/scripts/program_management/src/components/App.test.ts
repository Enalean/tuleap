/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import * as drekkenov from "@tuleap/drag-and-drop";
import type { ProjectFlag } from "@tuleap/vue3-breadcrumb-privacy";
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";
import App from "./App.vue";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import type { State } from "../type";
import type { ConfigurationState } from "../store/configuration";
import { createConfigurationModule } from "../store/configuration";

describe("App", () => {
    function createWrapper(is_configured: boolean, is_admin: boolean): VueWrapper {
        return shallowMount(App, {
            global: {
                ...getGlobalTestOptions({
                    state: { has_modal_error: false } as State,
                    modules: {
                        configuration: createConfigurationModule({
                            has_plan_permissions: true,
                            is_configured,
                            is_program_admin: is_admin,
                            public_name: "Fibrovasal phrenesis",
                            short_name: "fibrovasal-phrenesis",
                            project_icon: "",
                            privacy: {} as ProjectPrivacy,
                            flags: [] as ReadonlyArray<ProjectFlag>,
                            program_id: 775,
                        } as ConfigurationState),
                    },
                }),
            },
            directives: { "dompurify-html": jest.fn() },
        });
    }

    it("Displays the backlog section", () => {
        const wrapper = createWrapper(true, false);
        expect(wrapper.find("[data-test=backlog-section]").exists()).toBe(true);
        expect(wrapper.find("[data-test=configuration-empty-state]").exists()).toBe(false);
    });

    it("Displays an empty state when project has no linked team yet", () => {
        const wrapper = createWrapper(false, false);
        expect(wrapper.find("[data-test=backlog-section]").exists()).toBe(false);
        expect(wrapper.find("[data-test=configuration-empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=administrator-empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=regular-user-empty-state]").exists()).toBe(true);
    });

    it("Displays an empty state for administrator when project has no linked team yet", () => {
        const wrapper = createWrapper(false, true);
        expect(wrapper.find("[data-test=backlog-section]").exists()).toBe(false);
        expect(wrapper.find("[data-test=configuration-empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=administrator-empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=regular-user-empty-state]").exists()).toBe(false);
    });

    describe(`mounted()`, () => {
        it(`will create a "drek"`, () => {
            const init = jest.spyOn(drekkenov, "init");
            createWrapper(true, false);

            expect(init).toHaveBeenCalled();
        });
    });

    describe(`destroy()`, () => {
        it(`will destroy the "drek"`, () => {
            const mock_drek = { destroy: jest.fn() };
            jest.spyOn(drekkenov, "init").mockImplementation(() => mock_drek);
            const wrapper = createWrapper(true, false);
            wrapper.unmount();

            expect(mock_drek.destroy).toHaveBeenCalled();
        });
    });
});
