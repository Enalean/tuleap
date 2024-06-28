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
import DocumentLayout from "@/components/DocumentLayout.vue";
import DocumentContent from "@/components/DocumentContent.vue";
import TableOfContents from "@/components/TableOfContents.vue";

describe("DocumentLayout", () => {
    it("should display document content", () => {
        const wrapper = shallowMount(DocumentLayout);

        expect(wrapper.findComponent(DocumentContent).exists()).toBe(true);
    });

    it("should display table of contents", () => {
        const wrapper = shallowMount(DocumentLayout);

        expect(wrapper.findComponent(TableOfContents).exists()).toBe(true);
    });
});
