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
import EmptyState from "@/views/EmptyState.vue";
import DocumentLayout from "@/components/DocumentLayout.vue";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import NoAccessState from "@/views/NoAccessState.vue";
import DocumentView from "@/views/DocumentView.vue";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";
import ConfigurationPanel from "@/components/configuration/ConfigurationPanel.vue";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import type { Tracker } from "@/stores/configuration-store";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import type { SectionsStore } from "@/stores/useSectionsStore";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";

describe("DocumentView", () => {
    function setupInjectionKeys(
        can_user_edit_document: boolean,
        selected_tracker: Tracker | null,
        sections_store: SectionsStore,
    ): void {
        mockStrictInject([
            [CAN_USER_EDIT_DOCUMENT, can_user_edit_document],
            [CONFIGURATION_STORE, ConfigurationStoreStub.withSelectedTracker(selected_tracker)],
            [SECTIONS_STORE, sections_store],
        ]);
    }

    describe("when sections not found", () => {
        it("should display empty state view if user cannot edit document", () => {
            setupInjectionKeys(
                false,
                ConfigurationStoreStub.bugs,
                InjectedSectionsStoreStub.withLoadedSections([]),
            );
            const wrapper = shallowMount(DocumentView);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(false);
        });

        it("should display empty state view if user can edit document and the tracker is configured", () => {
            setupInjectionKeys(
                false,
                ConfigurationStoreStub.bugs,
                InjectedSectionsStoreStub.withLoadedSections([]),
            );
            const wrapper = shallowMount(DocumentView);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(false);
        });

        it("should display configuration screen if user can edit document and the tracker is not configured", () => {
            setupInjectionKeys(true, null, InjectedSectionsStoreStub.withLoadedSections([]));
            const wrapper = shallowMount(DocumentView);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(true);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(false);
        });
    });

    describe("when sections found", () => {
        it("should display document content view", () => {
            setupInjectionKeys(
                false,
                ConfigurationStoreStub.bugs,
                InjectedSectionsStoreStub.withLoadedSections([ArtifactSectionFactory.create()]),
            );
            const wrapper = shallowMount(DocumentView);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(true);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(false);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
        });
    });

    describe("when sections are loading", () => {
        it("should display document content view", () => {
            setupInjectionKeys(
                false,
                ConfigurationStoreStub.bugs,
                InjectedSectionsStoreStub.withLoadingSections(),
            );
            const wrapper = shallowMount(DocumentView);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(true);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(false);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
        });
    });

    describe("when the user is not allowed to access the document", () => {
        it("should display no access state view", () => {
            setupInjectionKeys(
                false,
                ConfigurationStoreStub.bugs,
                InjectedSectionsStoreStub.withSectionsInError(),
            );
            const wrapper = shallowMount(DocumentView);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(true);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(false);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
        });
    });
});
