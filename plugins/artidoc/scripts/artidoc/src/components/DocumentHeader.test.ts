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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import DocumentHeader from "@/components/DocumentHeader.vue";
import DocumentDropdown from "@/components/DocumentDropdown.vue";
import { TITLE } from "@/title-injection-key";

describe("DocumentHeader", () => {
    it("should display dropdown for document", () => {
        const wrapper = shallowMount(DocumentHeader, {
            global: {
                provide: {
                    [TITLE.valueOf()]: "My document",
                },
            },
        });

        expect(wrapper.findComponent(DocumentDropdown).exists()).toBe(true);
    });
});
