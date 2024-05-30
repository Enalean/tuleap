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

import { beforeAll, describe, expect, it, vi } from "vitest";
import type { ComponentPublicInstance } from "vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DocumentLayout from "@/components/DocumentLayout.vue";
import DocumentContent from "@/components/DocumentContent.vue";
import TableOfContents from "@/components/TableOfContents.vue";
import * as sectionsStore from "@/stores/useSectionsStore";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";

describe("DocumentLayout", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;
    beforeAll(() => {
        vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
            InjectedSectionsStoreStub.withLoadedSections([]),
        );
        wrapper = shallowMount(DocumentLayout);
    });
    it("should display document content", () => {
        expect(wrapper.findComponent(DocumentContent).exists()).toBe(true);
    });
    it("should display table of contents", () => {
        expect(wrapper.findComponent(TableOfContents).exists()).toBe(true);
    });
});
