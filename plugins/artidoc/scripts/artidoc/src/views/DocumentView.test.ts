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
import { shallowMount } from "@vue/test-utils";
import EmptyState from "@/views/EmptyState.vue";
import DocumentLayout from "@/components/DocumentLayout.vue";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";
import NoAccessState from "@/views/NoAccessState.vue";
import DocumentView from "@/views/DocumentView.vue";
import * as sectionsStore from "@/stores/useSectionsStore";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";

describe("DocumentView", () => {
    describe("when sections not found", () => {
        it("should display empty state view", () => {
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
                InjectedSectionsStoreStub.withLoadedSections([]),
            );
            const wrapper = shallowMount(DocumentView);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(false);
        });
    });

    describe("when sections found", () => {
        it("should display document content view", () => {
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
                InjectedSectionsStoreStub.withLoadedSections([ArtidocSectionFactory.create()]),
            );
            const wrapper = shallowMount(DocumentView);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(true);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
        });
    });

    describe("when sections are loading", () => {
        it("should display document content view", () => {
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
                InjectedSectionsStoreStub.withLoadingSections(),
            );
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
                InjectedSectionsStoreStub.withLoadingSections(),
            );
            const wrapper = shallowMount(DocumentView);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(true);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
        });
    });

    describe("when the user is not allowed to access the document", () => {
        it("should display no access state view", () => {
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
                InjectedSectionsStoreStub.withSectionsInError(),
            );
            const wrapper = shallowMount(DocumentView);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(true);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(false);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
        });
    });
});
