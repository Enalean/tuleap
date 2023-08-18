/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
import { createPlatformBannerAdminLocalVue } from "../helpers/local-vue-for-tests";
import ExpiredBannerInfoMessage from "./ExpiredBannerInfoMessage.vue";

describe("ExpiredBannerInfoMessage", () => {
    it("displays an info message when the banner is expired", async () => {
        const wrapper = shallowMount(ExpiredBannerInfoMessage, {
            localVue: await createPlatformBannerAdminLocalVue(),
            propsData: {
                message: "Some banner message",
                expiration_date: "1970-01-01T00:00:01+00:00",
            },
        });

        expect(wrapper.element.children).toBeDefined();
    });

    it("does not display the expired info message when no banner is set", async () => {
        const wrapper = shallowMount(ExpiredBannerInfoMessage, {
            localVue: await createPlatformBannerAdminLocalVue(),
            propsData: {
                message: "",
                expiration_date: "",
            },
        });

        expect(wrapper.element.children).toBeUndefined();
    });

    it("does not display the expired info message when the banner never expires", async () => {
        const wrapper = shallowMount(ExpiredBannerInfoMessage, {
            localVue: await createPlatformBannerAdminLocalVue(),
            propsData: {
                message: "Some message",
                expiration_date: "",
            },
        });

        expect(wrapper.element.children).toBeUndefined();
    });

    it("does not display the expired info message when the banner is not yet expired", async () => {
        const wrapper = shallowMount(ExpiredBannerInfoMessage, {
            localVue: await createPlatformBannerAdminLocalVue(),
            propsData: {
                message: "Some message",
                expiration_date: new Date(Date.now() + 3600 * 1000 * 24).toISOString(),
            },
        });

        expect(wrapper.element.children).toBeUndefined();
    });
});
