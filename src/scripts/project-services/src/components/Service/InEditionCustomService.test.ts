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
import type { VueWrapper } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../support/global-options-for-tests";
import type { Service } from "../../type";
import InEditionCustomService from "./InEditionCustomService.vue";
import ServiceOpenInNewTab from "./ServiceOpenInNewTab.vue";

describe(`InEditionCustomService`, () => {
    let service_prop: Service;

    beforeEach(() => {
        service_prop = {
            id: 101,
            icon_name: "",
            label: "",
            link: "",
            description: "",
            is_active: true,
            is_used: true,
            is_in_iframe: false,
            is_in_new_tab: false,
            rank: 11,
            is_project_scope: true,
            is_disabled_reason: "",
            short_name: "",
            is_link_customizable: false,
        };
    });

    function createWrapper(): VueWrapper {
        return shallowMount(InEditionCustomService, {
            global: { ...getGlobalTestOptions() },
            props: {
                service_prop,
            },
        });
    }

    describe(`When the service is already open in an iframe`, () => {
        it(`will show the switch input`, () => {
            service_prop.is_in_iframe = true;
            const wrapper = createWrapper();

            const iframe_switch = wrapper.find("[data-test=iframe-switch]");
            expect(iframe_switch.exists()).toBe(true);
        });

        it(`when I switch off "Open in iframe", it will show a deprecation warning`, async () => {
            service_prop.is_in_iframe = true;
            const wrapper = createWrapper();

            wrapper.get("[data-test=iframe-switch]").setValue(false);
            const updated_service = { ...service_prop, is_in_iframe: false };
            const new_props = {
                service_prop: updated_service,
                allowed_icons: {},
            };
            await wrapper.setProps(new_props);

            const deprecation_message = wrapper.find("[data-test=iframe-deprecation-warning]");
            expect(deprecation_message.exists()).toBe(true);
        });

        it(`when I also check "Is in new tab",
            it will disable "is in iframe" and show a warning`, async () => {
            service_prop.is_in_iframe = true;
            const wrapper = createWrapper();

            wrapper.findComponent(ServiceOpenInNewTab).vm.$emit("input", true);
            await wrapper.vm.$nextTick();

            const new_tab_warning = wrapper.find("[data-test=new-tab-warning]");
            expect(new_tab_warning.exists()).toBe(true);
        });

        it(`When the warning is shown and I uncheck "Is in new tab",
            it will hide the warning`, async () => {
            service_prop.is_in_iframe = true;
            const wrapper = createWrapper();

            wrapper.findComponent(ServiceOpenInNewTab).vm.$emit("input", true);
            await wrapper.vm.$nextTick();

            let new_tab_warning = wrapper.find("[data-test=new-tab-warning]");
            expect(new_tab_warning.exists()).toBe(true);

            wrapper.findComponent(ServiceOpenInNewTab).vm.$emit("input", false);
            await wrapper.vm.$nextTick();
            new_tab_warning = wrapper.find("[data-test=new-tab-warning]");

            expect(new_tab_warning.exists()).toBe(false);
        });
    });

    it(`When the service is not already open in an iframe,
        the switch input won't be displayed`, () => {
        const wrapper = createWrapper();

        const iframe_switch = wrapper.find("[data-test=iframe-switch]");
        expect(iframe_switch.exists()).toBe(false);
    });
});
