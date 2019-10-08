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
import App from "./App.vue";
import BannerDeleter from "./BannerDeleter.vue";
import { createProjectAdminBannerLocalVue } from "../helpers/local-vue-for-tests";
import * as rest_querier from "../api/rest-querier";

describe("App", () => {
    it("displays something when no banner is set", async () => {
        const wrapper = shallowMount(App, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: "",
                project_id: 108
            }
        });

        expect(wrapper.text()).not.toBe("");
    });

    it("displays message and remove button when banner is not empty", async () => {
        const banner_message = "<b>My banner content</b>";

        const wrapper = shallowMount(App, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: banner_message,
                project_id: 108
            }
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Should be able to send the deletion request", async () => {
        const wrapper = shallowMount(App, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: "some message",
                project_id: 108
            }
        });

        const reload_page = jest.spyOn(window.location, "reload").mockImplementation();
        const delete_banner = jest
            .spyOn(rest_querier, "deleteBannerForProject")
            .mockImplementation(() => {
                return Promise.resolve();
            });

        wrapper.find(BannerDeleter).vm.$emit("delete-banner");

        await wrapper.vm.$nextTick();

        expect(delete_banner).toHaveBeenCalledTimes(1);
        expect(reload_page).toHaveBeenCalledTimes(1);
    });

    it("Should display an error if banner deletion fails", async () => {
        const wrapper = shallowMount(App, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: "some message",
                project_id: 108
            }
        });

        jest.spyOn(rest_querier, "deleteBannerForProject").mockImplementation(() => {
            return Promise.reject(new Error("an error message"));
        });

        wrapper.find(BannerDeleter).vm.$emit("delete-banner");

        await wrapper.vm.$nextTick();

        expect(wrapper.element).toMatchSnapshot();
    });
});
