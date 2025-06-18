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
import InEditionCustomService from "./InEditionCustomService.vue";
import { getGlobalTestOptions } from "../../support/global-options-for-tests.js";

describe(`InEditionCustomService`, () => {
    let props;

    beforeEach(() => {
        props = {
            minimal_rank: 10,
            service: {
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
            },
            allowed_icons: {},
        };
    });

    function createWrapper() {
        return shallowMount(InEditionCustomService, {
            global: { ...getGlobalTestOptions() },
            props,
        });
    }

    describe(`When the service is already open in an iframe`, () => {
        it(`will show the switch input`, () => {
            props.service.is_in_iframe = true;
            const wrapper = createWrapper();

            const iframe_switch = wrapper.find("[data-test=iframe-switch]");
            expect(iframe_switch.exists()).toBe(true);
        });

        it(`when I switch off "Open in iframe", it will show a deprecation warning`, async () => {
            props.service.is_in_iframe = true;
            const wrapper = createWrapper();

            wrapper.get("[data-test=iframe-switch]").setChecked(false);
            const updated_service = { ...props.service, is_in_iframe: false };
            const new_props = { minimal_rank: 10, service: updated_service, allowed_icons: {} };
            await wrapper.setProps(new_props);

            const deprecation_message = wrapper.find("[data-test=iframe-deprecation-warning]");
            expect(deprecation_message.exists()).toBe(true);
        });

        it(`when I also check "Is in new tab",
            it will disable "is in iframe" and show a warning`, async () => {
            props.service.is_in_iframe = true;
            const wrapper = createWrapper();

            wrapper.vm.onNewTabChange(true);
            await wrapper.vm.$nextTick();

            const new_tab_warning = wrapper.find("[data-test=new-tab-warning]");
            expect(new_tab_warning.exists()).toBe(true);
        });

        it(`When the warning is shown and I uncheck "Is in new tab",
            it will hide the warning`, async () => {
            props.service.is_in_iframe = true;
            const wrapper = createWrapper();

            wrapper.vm.onNewTabChange(true);
            await wrapper.vm.$nextTick();

            let new_tab_warning = wrapper.find("[data-test=new-tab-warning]");
            expect(new_tab_warning.exists()).toBe(true);

            wrapper.vm.onNewTabChange(false);
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
