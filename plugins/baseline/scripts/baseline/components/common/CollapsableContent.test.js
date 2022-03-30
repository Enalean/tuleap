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
 *
 */

import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import CollapsableContent from "./CollapsableContent.vue";

describe("CollapsableContent", () => {
    const toggle_selector = '[data-test-action="toggle-expand-collapse"]';
    const header_slot_selector = '[data-test-type="header-slot"]';

    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(CollapsableContent, {
            localVue,
            slots: {
                default: '<div data-test-type="default-slot">Default slot</div>',
                header: '<div data-test-type="header-slot">Header slot</div>',
            },
        });
    });

    it("shows header slot", () => {
        expect(wrapper.get(header_slot_selector).exists()).toBeTruthy();
    });

    it("shows default slot", () => {
        expect(
            wrapper.get("[data-test=collapsible-slot]").element.style.getPropertyValue("display")
        ).not.toEqual("none");
    });

    describe("when toggle expand/collapse", () => {
        beforeEach(async () => {
            wrapper.get(toggle_selector).trigger("click");
            await wrapper.vm.$nextTick();
        });

        it("still shows header slot", () => {
            expect(wrapper.get(header_slot_selector).exists()).toBeTruthy();
        });

        it("hides default slot", () => {
            expect(
                wrapper
                    .get("[data-test=collapsible-slot]")
                    .element.style.getPropertyValue("display")
            ).toEqual("none");
        });

        describe("when toggle expand/collapse again", () => {
            beforeEach(async () => {
                wrapper.get(toggle_selector).trigger("click");
                await wrapper.vm.$nextTick();
            });

            it("shows default slot", () => {
                expect(
                    wrapper
                        .get("[data-test=collapsible-slot]")
                        .element.style.getPropertyValue("display")
                ).not.toEqual("none");
            });
        });
    });
});
