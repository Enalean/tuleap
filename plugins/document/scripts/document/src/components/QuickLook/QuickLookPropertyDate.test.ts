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

import { describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { DEFAULT_LOCALE } from "@tuleap/locale";
import QuickLookPropertyDate from "./QuickLookPropertyDate.vue";
import * as date_formatter from "../../helpers/date-formatter";
import type { Property } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import {
    DATE_FORMATTER,
    DATE_TIME_FORMATTER,
    RELATIVE_DATES_DISPLAY,
    USER_LOCALE,
} from "../../configuration-keys";

describe("QuickLookPropertyDate", () => {
    const mock_formatter = {
        format: vi.fn((date: string) => date),
    };

    function getWrapper(
        property: Property,
        is_obsolescence_date = false,
    ): VueWrapper<InstanceType<typeof QuickLookPropertyDate>> {
        return shallowMount(QuickLookPropertyDate, {
            props: { property, isObsolescenceDate: is_obsolescence_date },
            global: {
                ...getGlobalTestOptions({}),
                stubs: {
                    "tlp-relative-date": true,
                    "date-without-time": true,
                    "document-relative-date": true,
                },
                provide: {
                    [DATE_FORMATTER.valueOf()]: mock_formatter,
                    [DATE_TIME_FORMATTER.valueOf()]: mock_formatter,
                    [USER_LOCALE.valueOf()]: DEFAULT_LOCALE,
                    [RELATIVE_DATES_DISPLAY.valueOf()]: "relative_first-absolute_shown",
                },
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
    it(`Given an regular date
        Then it displays the formatted date with time`, () => {
        const property_date = {
            id: 100,
            name: "original date",
            short_name: "a_random_date",
            type: "date",
            list_value: null,
            value: "2019-07-02",
            post_processed_value: "2019-07-02",
        } as unknown as Property;

        const wrapper = getWrapper(property_date, false);

        expect(wrapper.find("[data-test=property-date-formatted-display]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=property-date-today]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-permanent]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-empty]").exists()).toBeFalsy();
    });
    it(`Given an obsolescence date
        Then it displays the formatted date without time`, () => {
        const property_date = {
            id: 100,
            name: "original date",
            short_name: "obsolescence_date",
            type: "date",
            list_value: null,
            value: "2019-07-02",
            post_processed_value: "2019-07-02",
        } as unknown as Property;

        const wrapper = getWrapper(property_date, true);

        expect(
            wrapper.find("[data-test=property-date-without-time-display]").exists(),
        ).toBeTruthy();
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

        const wrapper = getWrapper(property_date, true);

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

        vi.spyOn(date_formatter, "isToday").mockReturnValue(true);

        const wrapper = getWrapper(property_date, true);

        expect(wrapper.find("[data-test=property-date-formatted-display]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-today]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=property-date-permanent]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=property-date-empty]").exists()).toBeFalsy();
    });
});
