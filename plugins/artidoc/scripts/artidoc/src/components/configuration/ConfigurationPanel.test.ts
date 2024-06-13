/*
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

import { describe, expect, it, vi } from "vitest";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import SuccessFeedback from "@/components/configuration/SuccessFeedback.vue";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import ConfigurationPanel from "@/components/configuration/ConfigurationPanel.vue";

describe("ConfigurationPanel", () => {
    it("should display error feedback", () => {
        vi.spyOn(strict_inject, "strictInject").mockReturnValue(ConfigurationStoreStub.withError());

        const wrapper = shallowMount(ConfigurationPanel, {
            global: { plugins: [createGettext({ silent: true })] },
        });

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(false);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(true);
    });
});
