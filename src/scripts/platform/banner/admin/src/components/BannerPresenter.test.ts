/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { shallowMount } from "@vue/test-utils";
import BannerPresenter from "./BannerPresenter.vue";
import { createPlatformBannerAdminLocalVue } from "../helpers/local-vue-for-tests";

describe("BannerPresenter", () => {
    it("displays the banner and a checked switch if banner is set", async () => {
        const banner_message = "<b>My banner content</b>";

        const wrapper = shallowMount(BannerPresenter, {
            localVue: await createPlatformBannerAdminLocalVue(),
            propsData: {
                message: banner_message,
                importance: "critical",
                expiration_date: "",
                loading: false,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays a default message and an unchecked switch if banner is not set", async () => {
        const wrapper = shallowMount(BannerPresenter, {
            localVue: await createPlatformBannerAdminLocalVue(),
            propsData: {
                message: "",
                importance: "critical",
                expiration_date: "",
                loading: false,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("sets 'activated' to false on save-banner if the switch is clicked when banner is set", async () => {
        const wrapper = shallowMount(BannerPresenter, {
            localVue: await createPlatformBannerAdminLocalVue(),
            propsData: {
                message: "some message",
                importance: "critical",
                expiration_date: "",
                loading: false,
            },
        });

        const emitSpy = jest.spyOn(wrapper.vm, "$emit");

        wrapper.get("input").setChecked(false);
        wrapper.get("button").trigger("click");
        expect(emitSpy).toHaveBeenCalledWith("save-banner", {
            message: "some message",
            importance: "critical",
            expiration_date: "",
            activated: false,
        });
    });

    it("disables the whole form when on loading state", async () => {
        const wrapper = shallowMount(BannerPresenter, {
            localVue: await createPlatformBannerAdminLocalVue(),
            propsData: {
                message: "some message",
                importance: "critical",
                expiration_date: "",
                loading: true,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("emits a save-banner event with the message on click on the save button", async () => {
        const wrapper = shallowMount(BannerPresenter, {
            localVue: await createPlatformBannerAdminLocalVue(),
            propsData: {
                message: "somme message",
                importance: "critical",
                expiration_date: "",
                loading: false,
            },
        });

        const emitSpy = jest.spyOn(wrapper.vm, "$emit");
        const updated_message = "new message";
        const updated_importance = "standard";

        wrapper.setData({
            current_message: updated_message,
            current_importance: updated_importance,
        });

        wrapper.get("button").trigger("click");
        expect(emitSpy).toHaveBeenCalledWith("save-banner", {
            message: updated_message,
            importance: updated_importance,
            expiration_date: "",
            activated: true,
        });
    });

    it("does not trigger a save-banner event if the user gives an empty message and banner has not been deactivated", async () => {
        const wrapper = shallowMount(BannerPresenter, {
            localVue: await createPlatformBannerAdminLocalVue(),
            propsData: {
                message: "some message",
                importance: "critical",
                expiration_date: "",
                loading: false,
            },
        });

        const emitSpy = jest.spyOn(wrapper.vm, "$emit");

        wrapper.setData({ current_message: "" });

        wrapper.get("button").trigger("click");
        expect(emitSpy).not.toHaveBeenCalled();
    });
});
