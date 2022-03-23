/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import QuickLookPropertyDate from "./QuickLookPropertyDate.vue";

import localVue from "../../../helpers/local-vue";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";

import * as date_formatter from "../../../helpers/date-formatter";
import type { Property } from "../../../type";
import type { ConfigurationState } from "../../../store/configuration";

describe("QuickLookPropertyDate", () => {
    function getWrapper(property: Property): Wrapper<QuickLookPropertyDate> {
        return shallowMount(QuickLookPropertyDate, {
            localVue,
            propsData: { property },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { date_time_format: "d/m/Y H:i" } as ConfigurationState,
                    },
                }),
            },
            stubs: {
                "tlp-relative-date": true,
            },
        });
    }

    it(`Given a date
        Then it displays the formatted date`, () => {
        const property_date = {
            id: 100,
            name: "original date",
            type: "date",
            list_value: null,
            value: "2019-07-02",
            post_processed_value: "2019-07-02",
        } as unknown as Property;

        const wrapper = getWrapper(property_date);
        expect(wrapper.find("[data-test=property-date-formatted-display]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=property-date-today]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-permanent]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-empty]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-formatted-display]").exists()).toBeTruthy();
    });
    it(`Given a date without value
        Then it displays it as empty`, () => {
        const property_date = {
            id: 100,
            name: "original date",
            type: "date",
            list_value: null,
            value: null,
            post_processed_value: null,
        } as unknown as Property;

        const wrapper = getWrapper(property_date);
        expect(wrapper.find("[data-test=property-date-formatted-display]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-today]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-permanent]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-empty]").exists()).toBeTruthy();
    });
    it(`Given an obsolescence date
        Then it displays the formatted date like a regular date type property`, () => {
        const property_date = {
            id: 100,
            name: "original date",
            short_name: "obsolescence_date",
            type: "date",
            list_value: null,
            value: "2019-07-02",
            post_processed_value: "2019-07-02",
        } as unknown as Property;

        const wrapper = getWrapper(property_date);

        expect(wrapper.find("[data-test=property-date-formatted-display]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=property-date-today]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-permanent]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-empty]").exists()).toBeFalsy();
    });
    it(`Given an obsolescence date without value
        Then it displays it as permanent`, () => {
        const property_date = {
            id: 100,
            short_name: "obsolescence_date",
            name: "Obsolescence Date",
            type: "date",
            list_value: null,
            value: null,
            post_processed_value: null,
        } as unknown as Property;

        const wrapper = getWrapper(property_date);

        expect(wrapper.find("[data-test=property-date-formatted-display]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-today]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-permanent]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=property-date-empty]").exists()).toBeFalsy();
    });
    it(`Given an obsolescence date set as today
        Then it displays it as today`, () => {
        const property_date: Property = {
            id: 100,
            short_name: "obsolescence_date",
            name: "Obsolescence Date",
            type: "date",
            list_value: null,
            value: "2019-07-02",
            post_processed_value: "2019-07-02",
        } as unknown as Property;

        jest.spyOn(date_formatter, "isToday").mockReturnValue(true);

        const wrapper = getWrapper(property_date);

        expect(wrapper.find("[data-test=property-date-formatted-display]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-today]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=property-date-permanent]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-empty]").exists()).toBeFalsy();
    });
});
