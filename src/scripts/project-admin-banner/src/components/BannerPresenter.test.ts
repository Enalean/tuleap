/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
import { createProjectAdminBannerLocalVue } from "../helpers/local-vue-for-tests";

describe("BannerPresenter", () => {
    let banner_message: string, loading: boolean;
    beforeEach(() => {
        banner_message = "some message";
        loading = false;
    });

    async function getWrapper(): Promise<Wrapper<InstanceType<typeof BannerPresenter>>> {
        return shallowMount(BannerPresenter, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: banner_message,
                loading,
            },
        });
    }

    it("displays the banner and a checked switch if banner is set", async () => {
        banner_message = "<b>My banner content</b>";
        const wrapper = await getWrapper();
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays a default message and an unchecked switch if banner is not set", async () => {
        banner_message = "";
        const wrapper = await getWrapper();
        expect(wrapper.element).toMatchSnapshot();
    });

    it("sets 'activated' to false on save-banner if the switch is clicked when banner is set", async () => {
        const wrapper = await getWrapper();

        wrapper.get("input").setChecked(false);
        wrapper.get("button").trigger("click");

        const emitted = wrapper.emitted();
        expect(emitted).toHaveProperty("save-banner");
        const events = emitted["save-banner"];
        if (events === undefined) {
            throw Error("Expected a save-banner event to be emitted");
        }
        expect(events[0]).toStrictEqual([
            {
                message: "some message",
                activated: false,
            },
        ]);
    });

    it("disables the whole form when on loading state", async () => {
        loading = true;
        const wrapper = await getWrapper();

        expect(wrapper.element).toMatchSnapshot();
    });

    it("emits a save-banner event with the message on click on the save button", async () => {
        const wrapper = await getWrapper();

        const updated_message = "new message";
        wrapper.setData({ current_message: updated_message });

        wrapper.get("button").trigger("click");
        const emitted = wrapper.emitted();
        expect(emitted).toHaveProperty("save-banner");
        const events = emitted["save-banner"];
        if (events === undefined) {
            throw Error("Expected a save-banner event to be emitted");
        }
        expect(events[0]).toStrictEqual([{ message: updated_message, activated: true }]);
    });

    it("does not trigger a save-banner event if the user gives an empty message and banner has not been deactivated", async () => {
        const wrapper = await getWrapper();

        wrapper.setData({ current_message: "" });

        wrapper.get("button").trigger("click");
        const emitted = wrapper.emitted();
        expect(emitted).not.toHaveProperty("save-banner");
    });
});
