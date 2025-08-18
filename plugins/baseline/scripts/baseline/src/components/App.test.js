/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import { createRouter, createWebHistory } from "vue-router";
import { getGlobalTestOptions } from "../support/global-options-for-tests";
import App from "./App.vue";
import NotificationAlert from "./NotificationAlert.vue";
import { routes } from "../router";

describe("App", () => {
    function createWrapper(notification) {
        const router = createRouter({
            history: createWebHistory(),
            routes,
        });

        const config = getGlobalTestOptions({
            modules: {
                dialog_interface: {
                    namespaced: true,
                    state: {
                        notification,
                    },
                },
            },
        });
        return shallowMount(App, {
            global: {
                plugins: [...config.plugins, router],
            },
            props: {
                project_public_name: "Project Public Name",
                project_url: "/project_url",
                project_icon: "🌷",
                privacy: {},
                project_flags: [],
                is_admin: false,
                admin_url: "/admin/url",
            },
        });
    }

    it("Show notification", () => {
        const wrapper = createWrapper({ text: "A notification message" });
        expect(wrapper.findComponent(NotificationAlert).exists()).toBeTruthy();
    });

    it("Does not show notification", () => {
        const wrapper = createWrapper(null);
        expect(wrapper.findComponent(NotificationAlert).exists()).toBeFalsy();
    });
});
