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

import { beforeAll, describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { mount } from "@vue/test-utils";
import DocumentLayoutSkeleton from "@/views/DocumentViewSkeleton.vue";
import DocumentContent from "@/components/DocumentContent.vue";
import TableOfContents from "@/components/TableOfContents.vue";
import type { ComponentPublicInstance } from "vue";

describe("DocumentViewSkeleton", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;

    beforeAll(() => {
        wrapper = mount(DocumentLayoutSkeleton, {
            global: {
                stubs: ["table-of-contents", "document-content"],
            },
        });
    });
    describe("when the component is mounted", () => {
        it("should display a table of contents", () => {
            expect(wrapper.findComponent(TableOfContents).exists()).toBe(true);
        });
        it("should display a document content", () => {
            expect(wrapper.findComponent(DocumentContent).exists()).toBe(true);
        });
    });
});
