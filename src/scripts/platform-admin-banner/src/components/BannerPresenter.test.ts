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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import BannerPresenter from "./BannerPresenter.vue";
import { createPlatformBannerAdminLocalVue } from "../helpers/local-vue-for-tests";

describe("BannerPresenter", () => {
    let banner_message: string, is_loading: boolean;
    beforeEach(() => {
        banner_message = "some message";
        is_loading = false;
    });

    //eslint-disable-next-line @typescript-eslint/ban-ts-comment -- Temporary while we migrate to Vue 3
    //@ts-ignore
    async function getWrapper(): Promise<Wrapper<InstanceType<typeof BannerPresenter>>> {
        return shallowMount(BannerPresenter, {
            localVue: await createPlatformBannerAdminLocalVue(),
            propsData: {
                message: banner_message,
                importance: "critical",
                expiration_date: "",
                loading: is_loading,
            },
        });
    }

    it("displays the banner and a checked switch if banner is set", async () => {
        banner_message = "<b>My banner content</b>";

        const wrapper = await getWrapper();

        expect(wrapper.get<HTMLInputElement>("[data-test=banner-active]").element.checked).toBe(
            true,
        );
        expect(wrapper.get("[data-test=banner-message]").exists()).toBe(true);
    });

    it("displays a default message and an unchecked switch if banner is not set", async () => {
        banner_message = "";
        const wrapper = await getWrapper();

        expect(wrapper.get<HTMLInputElement>("[data-test=banner-active]").element.checked).toBe(
            false,
        );
        expect(wrapper.get("[data-test=banner-message]").isVisible()).toBe(false);
    });

    it("sets 'activated' to false on save-banner if the switch is clicked when banner is set", async () => {
        const wrapper = await getWrapper();

        wrapper.get("[data-test=banner-active]").setChecked(false);
        wrapper.get("[data-test=save-button]").trigger("click");

        const event = wrapper.emitted("save-banner");
        if (event === undefined) {
            throw Error("The 'save-banner' event should be emitted");
        }
        expect(event[0][0]).toStrictEqual({
            message: "some message",
            importance: "critical",
            expiration_date: "",
            activated: false,
        });
    });

    it("disables the whole form when on loading state", async () => {
        is_loading = true;
        const wrapper = await getWrapper();

        expect(wrapper.get("[data-test=banner-active-form-element]").classes()).toContain(
            "tlp-form-element-disabled",
        );
        expect(wrapper.get("[data-test=message-form-element]").classes()).toContain(
            "tlp-form-element-disabled",
        );
        expect(wrapper.get<HTMLButtonElement>("[data-test=save-button]").element.disabled).toBe(
            true,
        );
    });

    it("emits a save-banner event with the message on click on the save button", async () => {
        const wrapper = await getWrapper();

        const updated_message = "new message";
        const updated_importance = "standard";

        wrapper.setData({
            current_message: updated_message,
            current_importance: updated_importance,
        });

        wrapper.get("[data-test=save-button]").trigger("click");

        const event = wrapper.emitted("save-banner");
        if (event === undefined) {
            throw Error("The 'save-banner' event should be emitted");
        }
        expect(event[0][0]).toStrictEqual({
            message: updated_message,
            importance: updated_importance,
            expiration_date: "",
            activated: true,
        });
    });

    it("does not trigger a save-banner event if the user gives an empty message and banner has not been deactivated", async () => {
        const wrapper = await getWrapper();

        wrapper.setData({ current_message: "" });

        wrapper.get("[data-test=save-button]").trigger("click");

        expect(wrapper.emitted("save-banner")).toBeUndefined();
    });
});
