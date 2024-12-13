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
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import SectionHeader from "./header/SectionHeader.vue";
import SectionDescription from "./description/SectionDescription.vue";
import * as editor from "@/composables/useSectionEditor";
import * as upload_file from "@/composables/useUploadFile";
import SectionHeaderSkeleton from "./header/SectionHeaderSkeleton.vue";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";
import { SectionEditorStub } from "@/helpers/stubs/SectionEditorStub";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import { UploadFileStub } from "@/helpers/stubs/UploadFileStub";
import { SET_GLOBAL_ERROR_MESSAGE } from "@/global-error-message-injection-key";
import { createGettext } from "vue3-gettext";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";

describe("SectionContent", () => {
    describe.each([
        ["artifact", ArtifactSectionFactory],
        ["freetext", FreetextSectionFactory],
    ])("when the %s sections are loaded", (name, factory) => {
        let wrapper: VueWrapper<ComponentPublicInstance>;
        beforeAll(() => {
            vi.spyOn(editor, "useSectionEditor").mockReturnValue(
                SectionEditorStub.withEditableSection(),
            );
            vi.spyOn(upload_file, "useUploadFile").mockReturnValue(
                UploadFileStub.uploadNotInProgress(),
            );

            wrapper = shallowMount(SectionContent, {
                global: {
                    plugins: [createGettext({ silent: true })],
                    provide: {
                        [SECTIONS_STORE.valueOf()]: InjectedSectionsStoreStub.withLoadedSections(
                            [],
                        ),
                        [SET_GLOBAL_ERROR_MESSAGE.valueOf()]: true,
                    },
                },
                props: {
                    section: factory.create(),
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

    describe.each([
        ["artifact", ArtifactSectionFactory],
        ["freetext", FreetextSectionFactory],
    ])("when the %s sections are loading", (name, factory) => {
        let wrapper: VueWrapper<ComponentPublicInstance>;
        beforeAll(() => {
            vi.spyOn(editor, "useSectionEditor").mockReturnValue(
                SectionEditorStub.withEditableSection(),
            );
            vi.spyOn(upload_file, "useUploadFile").mockReturnValue(
                UploadFileStub.uploadNotInProgress(),
            );

            wrapper = shallowMount(SectionContent, {
                global: {
                    plugins: [createGettext({ silent: true })],
                    provide: {
                        [SECTIONS_STORE.valueOf()]: InjectedSectionsStoreStub.withLoadingSections(
                            [],
                        ),
                        [SET_GLOBAL_ERROR_MESSAGE.valueOf()]: true,
                    },
                },
                props: {
                    section: factory.create(),
                },
            });
        });

        it("should display a skeleton section title", () => {
            expect(wrapper.findComponent(SectionHeaderSkeleton).exists()).toBe(true);
            expect(wrapper.findComponent(SectionHeader).exists()).toBe(false);
        });
    });
});
