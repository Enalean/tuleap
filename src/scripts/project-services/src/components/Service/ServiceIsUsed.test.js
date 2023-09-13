/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
import ServiceIsUsed from "./ServiceIsUsed.vue";
import { createLocalVueForTests } from "../../support/local-vue";

describe(`ServiceIsUsed`, () => {
    let service_is_used, service_activation_disabled_message;

    beforeEach(() => {
        service_is_used = true;
        service_activation_disabled_message = "";
    });

    const getWrapper = async () => {
        const props = {
            id: 138,
            value: service_is_used,
            disabledReason: service_activation_disabled_message,
        };

        return shallowMount(ServiceIsUsed, {
            localVue: await createLocalVueForTests(),
            propsData: props,
        });
    };

    it(`keeps the checkbox enabled`, async () => {
        const wrapper = await getWrapper();
        const checkbox = wrapper.get("[data-test=service-is-used]");
        const message = wrapper.find("[data-test=service-disabled-message]");
        expect(checkbox.attributes("disabled")).toBeUndefined();
        expect(message.exists()).toBe(false);
    });

    it(`when the service is not used and there is a reason to prevent its activation,
        it will disable the checkbox and show the reason message`, async () => {
        service_is_used = false;
        service_activation_disabled_message = "It cannot be enabled";

        const wrapper = await getWrapper();

        const checkbox = wrapper.get("[data-test=service-is-used]");
        const message = wrapper.find("[data-test=service-disabled-message]");
        expect(checkbox.attributes("disabled")).toBeDefined();
        expect(message.exists()).toBe(true);
        expect(message.text()).toBe(service_activation_disabled_message);
    });

    it(`when the service is used and there is a reason to prevent its activation,
        it will keep the checkbox enabled and won't show the message
        to prevent locking between mutually-exclusive services`, async () => {
        service_activation_disabled_message = "It cannot be enabled";

        const wrapper = await getWrapper();

        const checkbox = wrapper.get("[data-test=service-is-used]");
        const message = wrapper.find("[data-test=service-disabled-message]");
        expect(checkbox.attributes("disabled")).toBeUndefined();
        expect(message.exists()).toBe(false);
    });
});
