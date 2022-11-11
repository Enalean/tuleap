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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue";
import CollapsableContent from "./CollapsableContent.vue";

describe("CollapsableContent", () => {
    const toggle_selector = '[data-test-action="toggle-expand-collapse"]';
    const header_slot_selector = '[data-test-type="header-slot"]';

    let wrapper: Wrapper<Vue>;

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
        const slot = wrapper.find("[data-test=collapsible-slot]").element;
        if (!(slot instanceof HTMLElement)) {
            throw Error("Unable to find the slot");
        }
        expect(slot.style.display).not.toBe("none");
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
            const slot = wrapper.find("[data-test=collapsible-slot]").element;
            if (!(slot instanceof HTMLElement)) {
                throw Error("Unable to find the slot");
            }
            expect(slot.style.display).toBe("none");
        });

        describe("when toggle expand/collapse again", () => {
            beforeEach(async () => {
                wrapper.get(toggle_selector).trigger("click");
                await wrapper.vm.$nextTick();
            });

            it("shows default slot", () => {
                const slot = wrapper.find("[data-test=collapsible-slot]").element;
                if (!(slot instanceof HTMLElement)) {
                    throw Error("Unable to find the slot");
                }
                expect(slot.style.display).not.toBe("none");
            });
        });
    });
});
