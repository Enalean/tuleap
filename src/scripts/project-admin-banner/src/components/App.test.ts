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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount, flushPromises } from "@vue/test-utils";
import { nextTick } from "vue";
import App from "./App.vue";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import BannerPresenter from "./BannerPresenter.vue";
import * as rest_querier from "../api/rest-querier";

describe("App", () => {
    let banner_message: string, location: Location;
    beforeEach(() => {
        banner_message = "some message";
        location = window.location;
    });

    const getWrapper = (): VueWrapper<InstanceType<typeof App>> => {
        return shallowMount(App, {
            global: { ...getGlobalTestOptions() },
            props: {
                message: banner_message,
                project_id: 108,
                location,
            },
        });
    };

    it("displays something when no banner is set", () => {
        banner_message = "";
        const wrapper = getWrapper();
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays message and remove button when banner is not empty", () => {
        banner_message = "<b>My banner content</b>";
        const wrapper = getWrapper();
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays success message when the banner has been successfully modified", async () => {
        banner_message = "";
        window.location.hash = "#banner-change-success";
        const wrapper = getWrapper();
        await nextTick();

        expect(wrapper.element).toMatchSnapshot();
        location.hash = "";
    });

    it("Should be able to send the deletion request", async () => {
        location = { ...window.location, reload: jest.fn() };
        const wrapper = getWrapper();

        const delete_banner = jest
            .spyOn(rest_querier, "deleteBannerForProject")
            .mockResolvedValue();

        wrapper
            .findComponent(BannerPresenter)
            .vm.$emit("save-banner", { message: "some message", activated: false });

        await nextTick();

        expect(delete_banner).toHaveBeenCalledTimes(1);
        expect(location.reload).toHaveBeenCalledTimes(1);
        expect(location.hash).toBe("#banner-change-success");
    });

    it("Should display an error if banner deletion fails", async () => {
        const wrapper = getWrapper();

        jest.spyOn(rest_querier, "deleteBannerForProject").mockRejectedValue(
            Error("an error message"),
        );

        const banner = wrapper.findComponent(BannerPresenter);
        banner.vm.$emit("save-banner", { message: "test", activated: false });
        await flushPromises();

        expect(banner.props().loading).toBe(false);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("Should be able to send the update request and lock form while doing it", async () => {
        location = { ...window.location, reload: jest.fn() };
        const wrapper = getWrapper();

        const save_banner = jest.spyOn(rest_querier, "saveBannerForProject").mockResolvedValue();

        const banner = wrapper.findComponent(BannerPresenter);
        banner.vm.$emit("save-banner", { message: "a new message", activated: true });
        await nextTick();

        expect(banner.props().loading).toBe(true);
        expect(save_banner).toHaveBeenCalledTimes(1);
        expect(location.reload).toHaveBeenCalledTimes(1);
        expect(location.hash).toBe("#banner-change-success");
    });

    it("Should display an error if banner update fails", async () => {
        const wrapper = getWrapper();

        jest.spyOn(rest_querier, "saveBannerForProject").mockRejectedValue(
            Error("Ooops something went wrong"),
        );

        const banner = wrapper.findComponent(BannerPresenter);
        banner.vm.$emit("save-banner", { message: "a new message", activated: true });
        await flushPromises();

        expect(banner.props().loading).toBe(false);
        expect(wrapper.element).toMatchSnapshot();
    });
});
