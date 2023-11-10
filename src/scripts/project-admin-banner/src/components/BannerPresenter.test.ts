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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import BannerPresenter from "./BannerPresenter.vue";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";

describe("BannerPresenter", () => {
    let banner_message: string, loading: boolean;
    beforeEach(() => {
        banner_message = "some message";
        loading = false;
    });

    function getWrapper(): VueWrapper<InstanceType<typeof BannerPresenter>> {
        return shallowMount(BannerPresenter, {
            global: { ...getGlobalTestOptions() },
            props: {
                message: banner_message,
                loading,
            },
        });
    }

    it("displays the banner and a checked switch if banner is set", () => {
        banner_message = "<b>My banner content</b>";
        const wrapper = getWrapper();
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays a default message and an unchecked switch if banner is not set", () => {
        banner_message = "";
        const wrapper = getWrapper();
        expect(wrapper.element).toMatchSnapshot();
    });

    it("sets 'activated' to false on save-banner if the switch is clicked when banner is set", () => {
        const wrapper = getWrapper();

        wrapper.get("input").setValue(false);
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

    it("disables the whole form when on loading state", () => {
        loading = true;
        const wrapper = getWrapper();

        expect(wrapper.element).toMatchSnapshot();
    });

    it("emits a save-banner event with the message on click on the save button", () => {
        const wrapper = getWrapper();

        const textarea = wrapper.find("[data-test=banner-message]");
        const updated_message = "new message";
        textarea.setValue(updated_message);

        wrapper.get("button").trigger("click");
        const emitted = wrapper.emitted();
        expect(emitted).toHaveProperty("save-banner");
        const events = emitted["save-banner"];
        if (events === undefined) {
            throw Error("Expected a save-banner event to be emitted");
        }
        expect(events[0]).toStrictEqual([{ message: updated_message, activated: true }]);
    });

    it("does not trigger a save-banner event if the user gives an empty message and banner has not been deactivated", () => {
        const wrapper = getWrapper();

        const textarea = wrapper.find("[data-test=banner-message]");
        textarea.setValue("");

        wrapper.get("button").trigger("click");
        const emitted = wrapper.emitted();
        expect(emitted).not.toHaveProperty("save-banner");
    });
});
