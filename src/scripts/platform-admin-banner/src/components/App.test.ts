/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import App from "./App.vue";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import BannerPresenter from "./BannerPresenter.vue";
import * as rest_querier from "../api/rest-querier";
import type { LocationWithHashReload } from "../helpers/LocationHelper";
import { LocationHelper } from "../helpers/LocationHelper";

jest.useFakeTimers();

const noop = (): void => {
    // Do nothing
};

describe("App", () => {
    let banner_message: string, fake_location: LocationWithHashReload;
    beforeEach(() => {
        banner_message = "some message";
        fake_location = { hash: "", reload: noop };
    });

    function getWrapper(): VueWrapper<InstanceType<typeof App>> {
        return shallowMount(App, {
            global: { ...getGlobalTestOptions() },
            props: {
                message: banner_message,
                importance: "critical",
                expiration_date: "",
                location_helper: LocationHelper(fake_location),
            },
        });
    }

    it("displays something when no banner is set", () => {
        banner_message = "";
        const wrapper = getWrapper();

        expect(wrapper.findComponent(BannerPresenter).exists()).toBe(true);
    });

    it("displays success message when the banner has been successfully modified", async () => {
        fake_location = { hash: "#banner-change-success", reload: noop };
        const wrapper = getWrapper();
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=success-feedback]").exists()).toBe(true);
    });

    it("Should be able to send the deletion request", async () => {
        fake_location = { hash: "", reload: noop };
        const reload = jest.spyOn(fake_location, "reload");
        const wrapper = getWrapper();

        const delete_banner = jest
            .spyOn(rest_querier, "deleteBannerForPlatform")
            .mockReturnValue(okAsync(null));

        wrapper.findComponent(BannerPresenter).vm.$emit("save-banner", {
            message: "some message",
            importance: "critical",
            activated: false,
        });
        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(BannerPresenter).props().loading).toBe(true);
        expect(delete_banner).toHaveBeenCalledTimes(1);
        expect(reload).toHaveBeenCalledTimes(1);
        expect(fake_location.hash).toBe("#banner-change-success");
    });

    it("Should display an error if banner deletion fails", async () => {
        const wrapper = getWrapper();

        jest.spyOn(rest_querier, "deleteBannerForPlatform").mockReturnValue(
            errAsync(Fault.fromMessage("an error message")),
        );

        wrapper
            .findComponent(BannerPresenter)
            .vm.$emit("save-banner", { message: "test", importance: "critical", activated: false });
        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(BannerPresenter).props().loading).toBe(false);
        expect(wrapper.find("[data-test=error-feedback]").exists()).toBe(true);
    });

    it("Should be able to send the update request and lock form while doing it", async () => {
        fake_location = { hash: "", reload: noop };
        const reload = jest.spyOn(fake_location, "reload");
        const wrapper = getWrapper();

        const save_banner = jest
            .spyOn(rest_querier, "saveBannerForPlatform")
            .mockReturnValue(okAsync(null));

        wrapper.findComponent(BannerPresenter).vm.$emit("save-banner", {
            message: "a new message",
            importance: "critical",
            expiration_date: "",
            activated: true,
        });
        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(BannerPresenter).props().loading).toBe(true);
        expect(save_banner).toHaveBeenCalledTimes(1);
        expect(reload).toHaveBeenCalledTimes(1);
        expect(fake_location.hash).toBe("#banner-change-success");
    });

    it("Should display an error if banner update fails", async () => {
        const wrapper = getWrapper();

        jest.spyOn(rest_querier, "saveBannerForPlatform").mockReturnValue(
            errAsync(Fault.fromMessage("Ooops something went wrong")),
        );

        wrapper.findComponent(BannerPresenter).vm.$emit("save-banner", {
            message: "a new message",
            importance: "critical",
            expiration_date: "",
            activated: true,
        });
        await jest.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(BannerPresenter).props().loading).toBe(false);
        expect(wrapper.find("[data-test=error-feedback]").exists()).toBe(true);
    });
});
