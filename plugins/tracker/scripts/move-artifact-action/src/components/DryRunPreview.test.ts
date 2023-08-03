/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import DryRunPreview from "./DryRunPreview.vue";
import DryRunNotMigratedFieldState from "./DryRunNotMigratedFieldState.vue";
import DryRunPartiallyMigratedFieldState from "./DryRunPartiallyMigratedFieldState.vue";
import DryRunFullyMigratedFieldState from "./DryRunFullyMigratedFieldState.vue";

describe("DryRunPreview", () => {
    it("should display the dry run preview", () => {
        const wrapper = shallowMount(DryRunPreview);

        expect(wrapper.findComponent(DryRunNotMigratedFieldState).exists()).toBe(true);
        expect(wrapper.findComponent(DryRunPartiallyMigratedFieldState).exists()).toBe(true);
        expect(wrapper.findComponent(DryRunFullyMigratedFieldState).exists()).toBe(true);
    });
});
