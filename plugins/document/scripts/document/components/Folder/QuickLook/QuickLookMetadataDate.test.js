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

import { shallowMount } from "@vue/test-utils";
import QuickLookMetadataDate from "./QuickLookMetadataDate.vue";

import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";

import * as date_formatter from "../../../helpers/date-formatter.js";

describe("QuickLookMetadataDate", () => {
    let metadata_factory, state, store;

    beforeEach(() => {
        state = {};

        const store_options = { state };

        store = createStoreMock(store_options);

        metadata_factory = (props = {}) => {
            return shallowMount(QuickLookMetadataDate, {
                localVue,
                propsData: { metadata: props },
                mocks: { $store: store },
            });
        };
    });

    it(`Given a date
        Then it displays the formatted date`, () => {
        const metadata_date = {
            id: 100,
            name: "original date",
            type: "date",
            list_value: null,
            value: "2019-07-02",
            post_processed_value: "2019-07-02",
        };
        store.state.date_time_format = "d/m/Y H:i";

        const wrapper = metadata_factory(metadata_date);
        expect(wrapper.contains("[data-test=metadata-date-formatted-display]")).toBeTruthy();
        expect(wrapper.contains("[data-test=metadata-date-today]")).toBeFalsy();
        expect(wrapper.contains("[data-test=metadata-date-permanent]")).toBeFalsy();
        expect(wrapper.contains("[data-test=metadata-date-empty]")).toBeFalsy();

        expect(
            wrapper
                .get("[data-test=metadata-date-formatted-display]")
                .element.getAttribute("data-tlp-tooltip")
        ).toContain("02/07/2019 00:00");
    });
    it(`Given a date without value
        Then it displays it as empty`, () => {
        const metadata_date = {
            id: 100,
            name: "original date",
            type: "date",
            list_value: null,
            value: null,
            post_processed_value: null,
        };

        store.state.date_time_format = "d/m/Y H:i";

        const wrapper = metadata_factory(metadata_date);
        expect(wrapper.contains("[data-test=metadata-date-formatted-display]")).toBeFalsy();
        expect(wrapper.contains("[data-test=metadata-date-today]")).toBeFalsy();
        expect(wrapper.contains("[data-test=metadata-date-permanent]")).toBeFalsy();
        expect(wrapper.contains("[data-test=metadata-date-empty]")).toBeTruthy();
    });
    it(`Given an obsolescence date
        Then it displays the formatted date like a regular date type metadata`, () => {
        const metadata_date = {
            id: 100,
            name: "original date",
            short_name: "obsolescence_date",
            type: "date",
            list_value: null,
            value: "2019-07-02",
            post_processed_value: "2019-07-02",
        };
        store.state.date_time_format = "d/m/Y H:i";

        const wrapper = metadata_factory(metadata_date);

        expect(wrapper.contains("[data-test=metadata-date-formatted-display]")).toBeTruthy();
        expect(wrapper.contains("[data-test=metadata-date-today]")).toBeFalsy();
        expect(wrapper.contains("[data-test=metadata-date-permanent]")).toBeFalsy();
        expect(wrapper.contains("[data-test=metadata-date-empty]")).toBeFalsy();
    });
    it(`Given an obsolescence date without value
        Then it displays it as permanent`, () => {
        const metadata_date = {
            id: 100,
            short_name: "obsolescence_date",
            name: "Obsolescence Date",
            type: "date",
            list_value: null,
            value: null,
            post_processed_value: null,
        };

        store.state.date_time_format = "d/m/Y H:i";

        const wrapper = metadata_factory(metadata_date);

        expect(wrapper.contains("[data-test=metadata-date-formatted-display]")).toBeFalsy();
        expect(wrapper.contains("[data-test=metadata-date-today]")).toBeFalsy();
        expect(wrapper.contains("[data-test=metadata-date-permanent]")).toBeTruthy();
        expect(wrapper.contains("[data-test=metadata-date-empty]")).toBeFalsy();
    });
    it(`Given an obsolescence date set as today
        Then it displays it as today`, () => {
        const metadata_date = {
            id: 100,
            short_name: "obsolescence_date",
            name: "Obsolescence Date",
            type: "date",
            list_value: null,
            value: "2019-07-02",
            post_processed_value: "2019-07-02",
        };

        store.state.date_time_format = "d/m/Y H:i";

        jest.spyOn(date_formatter, "isToday").mockReturnValue(true);

        const wrapper = metadata_factory(metadata_date);

        expect(wrapper.contains("[data-test=metadata-date-formatted-display]")).toBeFalsy();
        expect(wrapper.contains("[data-test=metadata-date-today]")).toBeTruthy();
        expect(wrapper.contains("[data-test=metadata-date-permanent]")).toBeFalsy();
        expect(wrapper.contains("[data-test=metadata-date-empty]")).toBeFalsy();
    });
});
