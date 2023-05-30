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
import { createProjectAdminBannerLocalVue } from "../helpers/local-vue-for-tests";
import BannerPresenter from "./BannerPresenter.vue";
import * as rest_querier from "../api/rest-querier";

describe("App", () => {
    it("displays something when no banner is set", async () => {
        const wrapper = shallowMount(App, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: "",
                project_id: 108,
                location: window.location,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays message and remove button when banner is not empty", async () => {
        const banner_message = "<b>My banner content</b>";

        const wrapper = shallowMount(App, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: banner_message,
                project_id: 108,
                location: window.location,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays success message when the banner has been successfully modified", async () => {
        location.hash = "#banner-change-success";
        const wrapper = shallowMount(App, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: "",
                project_id: 108,
                location: window.location,
            },
        });
        await wrapper.vm.$nextTick();

        expect(wrapper.element).toMatchSnapshot();
        location.hash = "";
    });

    it("Should be able to send the deletion request", async () => {
        const location = { ...window.location, reload: jest.fn() };
        const wrapper = shallowMount(App, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: "some message",
                project_id: 108,
                location,
            },
        });

        const delete_banner = jest
            .spyOn(rest_querier, "deleteBannerForProject")
            .mockImplementation(() => {
                return Promise.resolve();
            });

        wrapper
            .findComponent(BannerPresenter)
            .vm.$emit("save-banner", { message: "some message", activated: false });

        await wrapper.vm.$nextTick();

        expect(delete_banner).toHaveBeenCalledTimes(1);
        expect(location.reload).toHaveBeenCalledTimes(1);
        expect(location.hash).toBe("#banner-change-success");
    });

    it("Should display an error if banner deletion fails", async () => {
        const wrapper = shallowMount(App, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: "some message",
                project_id: 108,
                location: window.location,
            },
        });

        jest.spyOn(rest_querier, "deleteBannerForProject").mockImplementation(() => {
            return Promise.reject(new Error("an error message"));
        });

        wrapper
            .findComponent(BannerPresenter)
            .vm.$emit("save-banner", { message: "test", activated: false });
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(BannerPresenter).props().loading).toBe(false);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("Should be able to send the update request and lock form while doing it", async () => {
        const location: Location = { ...window.location, reload: jest.fn() };
        const wrapper = shallowMount(App, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: "some message",
                project_id: 108,
                location,
            },
        });

        const save_banner = jest
            .spyOn(rest_querier, "saveBannerForProject")
            .mockImplementation(() => {
                return Promise.resolve();
            });

        wrapper
            .findComponent(BannerPresenter)
            .vm.$emit("save-banner", { message: "a new message", activated: true });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(BannerPresenter).props().loading).toBe(true);
        expect(save_banner).toHaveBeenCalledTimes(1);
        expect(location.reload).toHaveBeenCalledTimes(1);
        expect(location.hash).toBe("#banner-change-success");
    });

    it("Should display an error if banner update fails", async () => {
        const wrapper = shallowMount(App, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: "some message",
                project_id: 108,
                location: window.location,
            },
        });

        jest.spyOn(rest_querier, "saveBannerForProject").mockImplementation(() => {
            return Promise.reject(new Error("Ooops something went wrong"));
        });

        wrapper
            .findComponent(BannerPresenter)
            .vm.$emit("save-banner", { message: "a new message", activated: true });
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(BannerPresenter).props().loading).toBe(false);
        expect(wrapper.element).toMatchSnapshot();
    });
});
