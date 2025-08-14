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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../support/global-options-for-tests";
import CollapsableContent from "./CollapsableContent.vue";

describe("CollapsableContent", () => {
    const toggle_selector = '[data-test-action="toggle-expand-collapse"]';
    const header_slot_selector = '[data-test-type="header-slot"]';

    let wrapper: VueWrapper<InstanceType<typeof CollapsableContent>>;

    beforeEach(() => {
        wrapper = shallowMount(CollapsableContent, {
            global: { ...getGlobalTestOptions() },
            slots: {
                default: '<div data-test-type="default-slot">Default slot</div>',
                header: '<div data-test-type="header-slot">Header slot</div>',
            },
        });
    });

    it("shows header slot", () => {
        expect(wrapper.get(header_slot_selector)).toBeTruthy();
    });

    it("shows default slot", () => {
        const slot = wrapper.get<HTMLElement>("[data-test=collapsible-slot]").element;
        expect(slot.style.display).not.toBe("none");
    });

    describe("when toggle expand/collapse", () => {
        beforeEach(async () => {
            await wrapper.get(toggle_selector).trigger("click");
        });

        it("still shows header slot", () => {
            expect(wrapper.get(header_slot_selector)).toBeTruthy();
        });

        it("hides default slot", () => {
            const slot = wrapper.get<HTMLElement>("[data-test=collapsible-slot]").element;
            expect(slot.style.display).toBe("none");
        });

        describe("when toggle expand/collapse again", () => {
            beforeEach(async () => {
                await wrapper.get(toggle_selector).trigger("click");
            });

            it("shows default slot", () => {
                const slot = wrapper.get<HTMLElement>("[data-test=collapsible-slot]").element;
                expect(slot.style.display).not.toBe("none");
            });
        });
    });
});
