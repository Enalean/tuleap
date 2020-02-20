/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { State } from "../../../store/type";
import { mount, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import StepTwo from "./StepTwo.vue";
import { createTrackerCreationLocalVue } from "../../../helpers/local-vue-for-tests";

describe("StepTwo", () => {
    async function getWrapper(
        state: State = {} as State,
        is_a_duplication = false,
        is_a_xml_import = false
    ): Promise<Wrapper<StepTwo>> {
        return mount(StepTwo, {
            mocks: {
                $store: createStoreMock({
                    state,
                    getters: {
                        is_a_duplication,
                        is_a_xml_import,
                        is_ready_to_submit: true
                    }
                })
            },
            localVue: await createTrackerCreationLocalVue(),
            stubs: {
                "field-csrf-token": true,
                "field-name": true,
                "field-shortname": true,
                "field-description": true,
                "field-tracker-template-id": true,
                "router-link": true
            }
        });
    }

    describe("Tracker duplication", () => {
        let wrapper: Wrapper<StepTwo>;

        beforeEach(async () => {
            wrapper = await getWrapper({} as State, true);
        });

        it("auto-fills the tracker name with the name of the selected tracker", () => {
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "initTrackerNameWithTheSelectedTemplateName"
            );
        });

        it("renders a field-tracker-template-id", () => {
            expect(wrapper.find("field-tracker-template-id-stub").exists()).toBe(true);
        });

        it("Sets the right encription type for the form", () => {
            expect(wrapper.find("#tracker-creation-form").attributes("enctype")).toEqual(
                "application/x-www-form-urlencoded"
            );
        });
    });

    describe("XML import", () => {
        let wrapper: Wrapper<StepTwo>;

        beforeEach(async () => {
            const file_input = document.implementation.createHTMLDocument().createElement("input");
            file_input.setAttribute("data-test", "injected-file-input");

            wrapper = await getWrapper(
                {
                    selected_xml_file_input: file_input
                } as State,
                false,
                true
            );
        });

        it("appends the file input filled during step 1 to the form", () => {
            expect(wrapper.find("[data-test=injected-file-input]").exists()).toBe(true);
            expect(wrapper.find("field-tracker-template-id-stub").exists()).toBe(false);
        });

        it("auto-fills the tracker name and shortname with the data contained in the selected xml file", () => {
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("initTrackerToBeCreatedFromXml");
        });

        it("Sets the right encription type for the form", () => {
            expect(wrapper.find("#tracker-creation-form").attributes("enctype")).toEqual(
                "multipart/form-data"
            );
        });
    });
});
