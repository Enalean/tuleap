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
import * as tuleap_strict_inject from "@tuleap/vue-strict-inject";
import { shallowMount } from "@vue/test-utils";
import EmptyState from "@/views/EmptyState.vue";
import DocumentLayout from "@/components/DocumentLayout.vue";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";
import NoAccessState from "@/views/NoAccessState.vue";
import DocumentView from "@/views/DocumentView.vue";
import * as sectionsStore from "@/stores/useSectionsStore";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";
import ConfigurationPanel from "@/components/configuration/ConfigurationPanel.vue";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { CONFIGURATION_STORE, type ConfigurationStore } from "@/stores/configuration-store";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";

describe("DocumentView", () => {
    function mockStrictInject(
        can_user_edit_document: boolean,
        selected_tracker_id: number | null,
    ): void {
        vi.spyOn(tuleap_strict_inject, "strictInject").mockImplementation(
            (key: StrictInjectionKey<unknown>): boolean | ConfigurationStore => {
                switch (key) {
                    case CAN_USER_EDIT_DOCUMENT:
                        return can_user_edit_document;

                    case CONFIGURATION_STORE:
                        if (selected_tracker_id) {
                            return ConfigurationStoreStub.withSelectedTracker(selected_tracker_id);
                        }

                        return ConfigurationStoreStub.withoutAllowedTrackers();
                    default:
                        throw new Error("Unknown injection key " + key);
                }
            },
        );
    }

    describe("when sections not found", () => {
        it("should display empty state view if user cannot edit document", () => {
            mockStrictInject(false, ConfigurationStoreStub.bugs.id);
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
                InjectedSectionsStoreStub.withLoadedSections([]),
            );
            const wrapper = shallowMount(DocumentView);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(false);
        });

        it("should display empty state view if user can edit document and the tracker is configured", () => {
            mockStrictInject(false, ConfigurationStoreStub.bugs.id);
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
                InjectedSectionsStoreStub.withLoadedSections([]),
            );
            const wrapper = shallowMount(DocumentView);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(false);
        });

        it("should display configuration screen if user can edit document and the tracker is not configured", () => {
            mockStrictInject(true, null);
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
                InjectedSectionsStoreStub.withLoadedSections([]),
            );
            const wrapper = shallowMount(DocumentView);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(true);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(false);
        });
    });

    describe("when sections found", () => {
        it("should display document content view", () => {
            mockStrictInject(false, ConfigurationStoreStub.bugs.id);
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
                InjectedSectionsStoreStub.withLoadedSections([ArtidocSectionFactory.create()]),
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
            mockStrictInject(false, ConfigurationStoreStub.bugs.id);
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
                InjectedSectionsStoreStub.withLoadingSections(),
            );
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
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
            mockStrictInject(false, ConfigurationStoreStub.bugs.id);
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
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
