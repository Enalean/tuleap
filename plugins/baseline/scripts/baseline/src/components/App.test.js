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
import VueRouter from "vue-router";
import { createLocalVueForTests } from "../support/local-vue.ts";
import App from "./App.vue";
import { createStoreMock } from "../support/store-wrapper.test-helper";
import store_options from "../store/store_options";
import NotificationAlert from "./NotificationAlert.vue";

describe("App", () => {
    let $store, wrapper;

    beforeEach(async () => {
        $store = createStoreMock(store_options);
        const router = new VueRouter();

        wrapper = shallowMount(App, {
            localVue: await createLocalVueForTests(),
            router,
            mocks: {
                $store,
            },
            propsData: {
                project_public_name: "Project Public Name",
                project_url: "/project_url",
                project_icon: "🌷",
                privacy: {},
                project_flags: [],
                is_admin: false,
                admin_url: "/admin/url",
            },
        });
    });

    describe("#changeTitle", () => {
        beforeEach(() => wrapper.vm.changeTitle("new title"));

        it('changes document title and suffix with "Tuleap"', () => {
            expect(document.title).toBe("new title - Tuleap");
        });
    });

    describe("With notification", () => {
        beforeEach(
            () =>
                ($store.state.dialog_interface.notification = {
                    text: "This is a failure notification",
                    class: "danger",
                }),
        );
        it("Show notification", () => {
            expect(wrapper.findComponent(NotificationAlert).exists()).toBeTruthy();
        });
    });

    describe("Without notification", () => {
        beforeEach(() => ($store.state.dialog_interface.notification = null));
        it("Show notification", () => {
            expect(wrapper.findComponent(NotificationAlert).exists()).toBeFalsy();
        });
    });
});
