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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SectionContent from "./SectionContent.vue";
import type { ComponentPublicInstance } from "vue";
import { ref } from "vue";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import SectionHeader from "./header/SectionHeader.vue";
import SectionDescription from "./description/SectionDescription.vue";
import * as editor from "@/composables/useSectionEditor";
import * as upload_file from "@/composables/useUploadFile";
import SectionHeaderSkeleton from "./header/SectionHeaderSkeleton.vue";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import { SectionEditorStub } from "@/helpers/stubs/SectionEditorStub";
import { SECTIONS_COLLECTION } from "@/sections/sections-collection-injection-key";
import { UploadFileStub } from "@/helpers/stubs/UploadFileStub";
import { SET_GLOBAL_ERROR_MESSAGE } from "@/global-error-message-injection-key";
import { createGettext } from "vue3-gettext";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { IS_LOADING_SECTIONS } from "@/is-loading-sections-injection-key";
import { skeleton_sections_collection } from "@/helpers/get-skeleton-sections-collection";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { SECTIONS_STATES_COLLECTION } from "@/sections/sections-states-collection-injection-key";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";

describe("SectionContent", () => {
    describe.each([
        ["artifact", ArtifactSectionFactory],
        ["freetext", FreetextSectionFactory],
    ])("when the %s sections are loaded", (name, factory) => {
        let wrapper: VueWrapper<ComponentPublicInstance>;
        beforeAll(() => {
            vi.spyOn(editor, "useSectionEditor").mockReturnValue(SectionEditorStub.build());
            vi.spyOn(upload_file, "useUploadFile").mockReturnValue(
                UploadFileStub.uploadNotInProgress(),
            );

            const states_collection = SectionsStatesCollectionStub.build();
            const section = ReactiveStoredArtidocSectionStub.fromSection(factory.create());

            states_collection.createStateForSection(section.value);

            wrapper = shallowMount(SectionContent, {
                global: {
                    plugins: [createGettext({ silent: true })],
                    provide: {
                        [SECTIONS_COLLECTION.valueOf()]: SectionsCollectionStub.withSections([]),
                        [SECTIONS_STATES_COLLECTION.valueOf()]: states_collection,
                        [SET_GLOBAL_ERROR_MESSAGE.valueOf()]: true,
                        [IS_LOADING_SECTIONS.valueOf()]: ref(false),
                        [DOCUMENT_ID.valueOf()]: 123,
                    },
                },
                props: {
                    section,
                },
            });
        });

        it("should display a section title", () => {
            expect(wrapper.findComponent(SectionHeader).exists()).toBe(true);
            expect(wrapper.findComponent(SectionHeaderSkeleton).exists()).toBe(false);
        });

        it("should display a section description", () => {
            expect(wrapper.findComponent(SectionDescription).exists()).toBe(true);
        });
    });

    describe.each([["artifact"], ["freetext"]])("when the %s sections are loading", () => {
        let wrapper: VueWrapper<ComponentPublicInstance>;
        beforeAll(() => {
            vi.spyOn(editor, "useSectionEditor").mockReturnValue(SectionEditorStub.build());
            vi.spyOn(upload_file, "useUploadFile").mockReturnValue(
                UploadFileStub.uploadNotInProgress(),
            );

            const states_collection = SectionsStatesCollectionStub.build();
            states_collection.createStateForSection(skeleton_sections_collection[0]);

            wrapper = shallowMount(SectionContent, {
                global: {
                    plugins: [createGettext({ silent: true })],
                    provide: {
                        [SECTIONS_COLLECTION.valueOf()]: SectionsCollectionStub.withSections(
                            skeleton_sections_collection,
                        ),
                        [SECTIONS_STATES_COLLECTION.valueOf()]: states_collection,
                        [SET_GLOBAL_ERROR_MESSAGE.valueOf()]: true,
                        [IS_LOADING_SECTIONS.valueOf()]: ref(true),
                        [DOCUMENT_ID.valueOf()]: 123,
                    },
                },
                props: {
                    section: ref(skeleton_sections_collection[0]),
                },
            });
        });

        it("should display a skeleton section title", () => {
            expect(wrapper.findComponent(SectionHeaderSkeleton).exists()).toBe(true);
            expect(wrapper.findComponent(SectionHeader).exists()).toBe(false);
        });
    });
});
