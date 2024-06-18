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
import WidgetQueryDisplayer from "./WidgetQueryDisplayer.vue";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import { injected_query, StubInjectionSymbols } from "../../tests/injection-symbols-stub";
import * as strict_inject from "@tuleap/vue-strict-inject";

describe("Given a timetracking management widget query displayer", () => {
    function getWidgetQueryDisplayerInstance(): VueWrapper {
        return shallowMount(WidgetQueryDisplayer, {
            global: {
                ...getGlobalTestOptions(),
            },
        });
    }

    describe("When query is displaying", () => {
        it("Then it should display the start date", () => {
            vi.spyOn(strict_inject, "strictInject").mockImplementation(
                StubInjectionSymbols.withDefaults(),
            );

            const wrapper = getWidgetQueryDisplayerInstance();

            const start_date = wrapper.find("[data-test=start-date]");

            expect(start_date.text()).equals(injected_query.getQuery().start_date);
        });

        it("Then it should display the end date", () => {
            vi.spyOn(strict_inject, "strictInject").mockImplementation(
                StubInjectionSymbols.withDefaults(),
            );

            const wrapper = getWidgetQueryDisplayerInstance();

            const end_date = wrapper.find("[data-test=end-date]");

            expect(end_date.text()).equals(injected_query.getQuery().end_date);
        });
    });
});
