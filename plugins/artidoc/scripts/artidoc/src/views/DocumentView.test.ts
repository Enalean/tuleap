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

import { describe, beforeEach, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { ref } from "vue";
import type { Ref } from "vue";
import EmptyState from "@/views/EmptyState.vue";
import DocumentLayout from "@/components/DocumentLayout.vue";
import NoAccessState from "@/views/NoAccessState.vue";
import DocumentView from "@/views/DocumentView.vue";
import ConfigurationPanel from "@/components/configuration/ConfigurationPanel.vue";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import type { Tracker } from "@/stores/configuration-store";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import {
    IS_LOADING_SECTIONS,
    IS_LOADING_SECTIONS_FAILED,
} from "@/is-loading-sections-injection-key";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";

describe("DocumentView", () => {
    let is_loading_sections: Ref<boolean>, is_loading_sections_failed: Ref<boolean>;

    beforeEach(() => {
        is_loading_sections = ref(false);
        is_loading_sections_failed = ref(false);
    });

    function getWrapper(
        can_user_edit_document: boolean,
        selected_tracker: Tracker | null,
        sections_collection: SectionsCollection,
    ): VueWrapper {
        return shallowMount(DocumentView, {
            global: {
                provide: {
                    [CAN_USER_EDIT_DOCUMENT.valueOf()]: can_user_edit_document,
                    [CONFIGURATION_STORE.valueOf()]:
                        ConfigurationStoreStub.withSelectedTracker(selected_tracker),
                    [SECTIONS_COLLECTION.valueOf()]: sections_collection,
                    [IS_LOADING_SECTIONS.valueOf()]: is_loading_sections,
                    [IS_LOADING_SECTIONS_FAILED.valueOf()]: is_loading_sections_failed,
                },
            },
        });
    }

    describe("when sections not found", () => {
        it("should display empty state view if user cannot edit document", () => {
            const wrapper = getWrapper(
                false,
                ConfigurationStoreStub.bugs,
                SectionsCollectionStub.withSections([]),
            );

            expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(false);
        });

        it("should display empty state view if user can edit document and the tracker is configured", () => {
            const wrapper = getWrapper(
                false,
                ConfigurationStoreStub.bugs,
                SectionsCollectionStub.withSections([]),
            );

            expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(false);
        });

        it("should display configuration screen if user can edit document and the tracker is not configured", () => {
            const wrapper = getWrapper(true, null, SectionsCollectionStub.withSections([]));

            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(true);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(false);
        });
    });

    describe("when sections found", () => {
        it("should display document content view", () => {
            const wrapper = getWrapper(
                false,
                ConfigurationStoreStub.bugs,
                SectionsCollectionStub.withSections([ArtifactSectionFactory.create()]),
            );

            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(true);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(false);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
        });
    });

    describe("when sections are loading", () => {
        it("should display document content view", () => {
            is_loading_sections.value = true;

            const wrapper = getWrapper(
                false,
                ConfigurationStoreStub.bugs,
                SectionsCollectionStub.withSections([]),
            );

            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(true);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(false);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
        });
    });

    describe("when the user is not allowed to access the document", () => {
        it("should display no access state view", () => {
            is_loading_sections_failed.value = true;

            const wrapper = getWrapper(
                false,
                ConfigurationStoreStub.bugs,
                SectionsCollectionStub.withSections([]),
            );

            expect(wrapper.findComponent(NoAccessState).exists()).toBe(true);
            expect(wrapper.findComponent(ConfigurationPanel).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentLayout).exists()).toBe(false);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
        });
    });
});
