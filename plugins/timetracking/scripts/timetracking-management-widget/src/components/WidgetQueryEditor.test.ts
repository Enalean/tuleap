/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import WidgetQueryEditor from "./WidgetQueryEditor.vue";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import { PredefinedTimePeriodsVueStub } from "../../tests/stubs/PredefinedTimePeriodsVueStub";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { StubInjectionSymbols } from "../../tests/injection-symbols-stub";

vi.mock("tlp", () => ({
    datePicker: (): { setDate(): void } => ({
        setDate: (): void => {
            // Do nothing
        },
    }),
}));

describe("Given a timetracking management widget query editor", () => {
    function getWidgetQueryEditorInstance(): VueWrapper {
        vi.spyOn(strict_inject, "strictInject").mockImplementation(
            StubInjectionSymbols.withDefaults(),
        );

        return shallowMount(WidgetQueryEditor, {
            global: {
                ...getGlobalTestOptions(),
                stubs: { "tuleap-predefined-time-period-select": PredefinedTimePeriodsVueStub },
            },
        });
    }

    it("When the submit button is clicked, dates are updated and the edition mode is closed", () => {
        const wrapper = getWidgetQueryEditorInstance();
        const start_date_input = wrapper.find<HTMLInputElement>("[data-test=start-date-input]");
        const end_date_input = wrapper.find<HTMLInputElement>("[data-test=end-date-input]");

        start_date_input.setValue("2024-05-10");
        end_date_input.setValue("2024-05-20");
        wrapper.find("[data-test=search-button]").trigger("click");

        const close_edit_mode_event = wrapper.emitted("closeEditMode");

        expect(start_date_input.element.value).equals("2024-05-10");
        expect(end_date_input.element.value).equals("2024-05-20");
        expect(close_edit_mode_event).toBeDefined();
    });

    it("When the cancel button is clicked, dates aren't updated and the edition mode is closed", () => {
        const wrapper = getWidgetQueryEditorInstance();

        wrapper.find("[data-test=cancel-button]").trigger("click");

        const set_dates_event = wrapper.emitted("setDates");
        const close_edit_mode_event = wrapper.emitted("closeEditMode");

        expect(set_dates_event).toBeUndefined();
        expect(close_edit_mode_event).toBeDefined();
    });

    it("When start date is selected manually, then the selected predefined time period should be cleared", async () => {
        const wrapper = getWidgetQueryEditorInstance();
        const predefined_time_period_stub = wrapper.findComponent(PredefinedTimePeriodsVueStub);

        const input = wrapper.find<HTMLInputElement>("[data-test=start-date-input]");
        await input.setValue("2024-23-05");

        expect(predefined_time_period_stub.vm.getCurrentlySelectedPredefinedTimePeriod()).toBe("");
    });

    it("When end date is selected manually, then the selected predefined time period should be cleared", async () => {
        const wrapper = getWidgetQueryEditorInstance();

        const predefined_time_period_stub = wrapper.findComponent(PredefinedTimePeriodsVueStub);

        const input = wrapper.find<HTMLInputElement>("[data-test=end-date-input]");
        await input.setValue("2024-23-05");

        expect(predefined_time_period_stub.vm.getCurrentlySelectedPredefinedTimePeriod()).toBe("");
    });
});
