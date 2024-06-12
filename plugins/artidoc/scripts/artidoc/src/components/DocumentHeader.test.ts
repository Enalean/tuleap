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
import { shallowMount } from "@vue/test-utils";
import DocumentHeader from "@/components/DocumentHeader.vue";
import ConfigurationModal from "@/components/configuration/ConfigurationModal.vue";

vi.mock("@tuleap/vue-strict-inject");

describe("DocumentHeader", () => {
    it("should display configuration if user can edit document", () => {
        vi.spyOn(strict_inject, "strictInject").mockReturnValue(true);

        const wrapper = shallowMount(DocumentHeader);

        expect(wrapper.findComponent(ConfigurationModal).exists()).toBe(true);
    });

    it("should not display configuration if user cannot edit document", () => {
        vi.spyOn(strict_inject, "strictInject").mockReturnValue(false);

        const wrapper = shallowMount(DocumentHeader);

        expect(wrapper.findComponent(ConfigurationModal).exists()).toBe(false);
    });
});
