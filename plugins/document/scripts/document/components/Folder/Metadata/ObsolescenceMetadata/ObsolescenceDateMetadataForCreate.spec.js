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
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";
import localVue from "../../../../helpers/local-vue.js";
import ObsolescenceDateMetadataForCreate from "./ObsolescenceDateMetadataForCreate.vue";
import moment from "moment/moment";

describe("ObsolescenceDateMetadataForCreate", () => {
    let metadata_factory, state, store;
    beforeEach(() => {
        state = {
            is_obsolescence_date_metadata_used: false
        };

        const store_options = { state };

        store = createStoreMock(store_options);

        metadata_factory = (props = {}) => {
            return shallowMount(ObsolescenceDateMetadataForCreate, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store }
            });
        };
    });
    describe("Component display", () => {
        it(`Displays the component if the obsolescence date metadata is used`, () => {
            const wrapper = metadata_factory();

            store.state.is_obsolescence_date_metadata_used = true;

            expect(wrapper.find("[data-test=obsolescence-date-metadata]").exists()).toBeTruthy();
        });
        it(`Does not display the component if the obsolescence date metadata is not used`, () => {
            const wrapper = metadata_factory();

            store.state.is_obsolescence_date_metadata_used = false;

            expect(wrapper.find("[data-test=obsolescence-date-metadata]").exists()).toBeFalsy();
        });

        describe(`The value of the payload when the input event is emitted`, () => {
            it(`should be null if the option "permanent" is chosen by the user `, () => {
                const wrapper = metadata_factory();
                store.state.is_obsolescence_date_metadata_used = true;

                const select = wrapper.find("[data-test=document-obsolescence-date-select]")
                    .element;
                select.value = "permanent";
                select.dispatchEvent(new Event("input"));

                expect(wrapper.emitted().input[0]).toEqual([null]);
            });
            it(`should be the current day + 3 months if the option "3months" is chosen by the user `, () => {
                const wrapper = metadata_factory();
                store.state.is_obsolescence_date_metadata_used = true;

                const select = wrapper.find("[data-test=document-obsolescence-date-select]")
                    .element;
                select.value = "3";
                select.dispatchEvent(new Event("input"));

                const current_date = moment().format("YYYY-MM-DD");

                const expected_date = moment(current_date, "YYYY-MM-DD")
                    .add(3, "M")
                    .format("YYYY-MM-DD");

                expect(wrapper.emitted().input[0]).toEqual([expected_date]);
            });
            describe(`The binding between the select box and the date input`, () => {
                it(`When the user click on the date input then the value of the select should be 'Fixed date`, () => {
                    const wrapper = metadata_factory();
                    store.state.is_obsolescence_date_metadata_used = true;

                    const select_date = wrapper.find(
                        "[data-test=document-obsolescence-date-select]"
                    ).element;
                    expect(select_date.value).toEqual("permanent");

                    const obsolescence_date_input = wrapper.find(
                        "[data-test=document-obsolescence-date-input]"
                    );
                    obsolescence_date_input.element.value = "2019-02-02";
                    obsolescence_date_input.trigger("click");

                    expect(select_date.value).toEqual("fixed");
                });
            });
            describe(`The checking of the obsolescence date`, () => {
                it(`displays error if the obsolescence date is anterior the current date`, async () => {
                    const wrapper = metadata_factory();
                    store.state.is_obsolescence_date_metadata_used = true;

                    const obsolescence_date_input = wrapper.find(
                        "[data-test=document-obsolescence-date-input]"
                    );
                    obsolescence_date_input.element.value = "2019-02-02";
                    obsolescence_date_input.trigger("click");

                    await wrapper.vm.$nextTick().then(() => {});

                    expect(
                        wrapper.contains("[data-test=obsolescence-date-error-message]")
                    ).toBeTruthy();
                });
                it(`displays no error if the obsolescence date is after the current date`, async () => {
                    const wrapper = metadata_factory();
                    store.state.is_obsolescence_date_metadata_used = true;

                    const future_date = moment()
                        .add(3, "day")
                        .format("YYYY-MM-DD");
                    const obsolescence_date_input = wrapper.find(
                        "[data-test=document-obsolescence-date-input]"
                    );
                    obsolescence_date_input.element.value = future_date;
                    obsolescence_date_input.trigger("click");

                    await wrapper.vm.$nextTick().then(() => {});

                    expect(
                        wrapper.contains("[data-test=obsolescence-date-error-message]")
                    ).toBeFalsy();
                });

                it(`displays error if the obsolescence date is the current date at creation`, async () => {
                    const props = {
                        isInUpdateContext: false
                    };
                    const wrapper = metadata_factory(props);
                    store.state.is_obsolescence_date_metadata_used = true;

                    const current_date = moment().format("YYYY-MM-DD");
                    const obsolescence_date_input = wrapper.find(
                        "[data-test=document-obsolescence-date-input]"
                    );
                    obsolescence_date_input.element.value = current_date;
                    obsolescence_date_input.trigger("click");

                    await wrapper.vm.$nextTick().then(() => {});

                    expect(
                        wrapper.contains("[data-test=obsolescence-date-error-message]")
                    ).toBeTruthy();
                });

                it(`displays error if the obsolescence date is not well formatted`, async () => {
                    const props = {
                        isInUpdateContext: true
                    };
                    const wrapper = metadata_factory(props);
                    store.state.is_obsolescence_date_metadata_used = true;

                    const obsolescence_date_input = wrapper.find(
                        "[data-test=document-obsolescence-date-input]"
                    );
                    obsolescence_date_input.element.value = "2044-81-02";
                    obsolescence_date_input.trigger("click");

                    await wrapper.vm.$nextTick().then(() => {});

                    expect(
                        wrapper.contains("[data-test=obsolescence-date-error-message]")
                    ).toBeTruthy();
                });
            });
        });
    });
});
