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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import App from "./App.vue";
import { createProgramManagementLocalVue } from "../helpers/local-vue-for-test";
import * as drekkenov from "@tuleap/drag-and-drop";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("App", () => {
    async function createWrapper(is_configured: boolean, is_admin: boolean): Promise<Wrapper<App>> {
        return shallowMount(App, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        has_modal_error: false,
                        configuration: {
                            has_plan_permissions: true,
                            is_configured: is_configured,
                            is_admin: is_admin,
                        },
                    },
                }),
            },
            localVue: await createProgramManagementLocalVue(),
        });
    }

    it("Displays the backlog section", async () => {
        const wrapper = await createWrapper(true, false);
        expect(wrapper.find("[data-test=backlog-section]").exists()).toBe(true);
        expect(wrapper.find("[data-test=configuration-empty-state]").exists()).toBe(false);
    });

    it("Displays an empty state when project has no linked team yet", async () => {
        const wrapper = await createWrapper(false, false);
        expect(wrapper.find("[data-test=backlog-section]").exists()).toBe(false);
        expect(wrapper.find("[data-test=configuration-empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=administrator-empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=regular-user-empty-state]").exists()).toBe(true);
    });

    it("Displays an empty state for administrator when project has no linked team yet", async () => {
        const wrapper = await createWrapper(false, true);
        expect(wrapper.find("[data-test=backlog-section]").exists()).toBe(false);
        expect(wrapper.find("[data-test=configuration-empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=administrator-empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=regular-user-empty-state]").exists()).toBe(true);
    });

    describe(`mounted()`, () => {
        it(`will create a "drek"`, async () => {
            const init = jest.spyOn(drekkenov, "init");
            await createWrapper(true, false);

            expect(init).toHaveBeenCalled();
        });
    });

    describe(`destroy()`, () => {
        it(`will destroy the "drek"`, async () => {
            const mock_drek = {
                destroy: jest.fn(),
            };
            jest.spyOn(drekkenov, "init").mockImplementation(() => mock_drek);
            const wrapper = await createWrapper(true, false);
            wrapper.destroy();

            expect(mock_drek.destroy).toHaveBeenCalled();
        });
    });
});
