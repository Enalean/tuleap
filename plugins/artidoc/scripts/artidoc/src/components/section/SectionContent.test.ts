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
import { beforeEach, describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import { ref } from "vue";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import { SET_GLOBAL_ERROR_MESSAGE } from "@/global-error-message-injection-key";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import { IS_LOADING_SECTIONS } from "@/is-loading-sections-injection-key";
import { skeleton_sections_collection } from "@/helpers/get-skeleton-sections-collection";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { SECTIONS_STATES_COLLECTION } from "@/sections/states/sections-states-collection-injection-key";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import SectionContent from "./SectionContent.vue";
import SectionHeader from "./header/SectionHeader.vue";
import SectionDescription from "./description/SectionDescription.vue";
import SectionHeaderSkeleton from "./header/SectionHeaderSkeleton.vue";
import { FILE_UPLOADS_COLLECTION } from "@/sections/attachments/sections-file-uploads-collection-injection-key";
import { FileUploadsCollectionStub } from "@/helpers/stubs/FileUploadsCollectionStub";
import { UPLOAD_MAX_SIZE } from "@/max-upload-size-injecion-keys";
import ReadonlyFields from "@/components/section/readonly-fields/ReadonlyFields.vue";

describe("SectionContent", () => {
    let is_loading_sections: boolean;

    beforeEach(() => {
        is_loading_sections = false;
    });

    const getWrapper = (artidoc_section: ArtidocSection): VueWrapper => {
        const states_collection = SectionsStatesCollectionStub.build();
        const section = ReactiveStoredArtidocSectionStub.fromSection(artidoc_section);

        states_collection.createStateForSection(section);

        return shallowMount(SectionContent, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [SECTIONS_COLLECTION.valueOf()]:
                        SectionsCollectionStub.fromReactiveStoredArtifactSections([section]),
                    [FILE_UPLOADS_COLLECTION.valueOf()]:
                        FileUploadsCollectionStub.withoutUploadsInProgress(),
                    [SECTIONS_STATES_COLLECTION.valueOf()]: states_collection,
                    [SET_GLOBAL_ERROR_MESSAGE.valueOf()]: true,
                    [IS_LOADING_SECTIONS.valueOf()]: ref(is_loading_sections),
                    [DOCUMENT_ID.valueOf()]: 123,
                    [UPLOAD_MAX_SIZE.valueOf()]: 100,
                },
            },
            props: {
                section,
            },
        });
    };

    it.each([
        ["artifact", ArtifactSectionFactory.create()],
        ["freetext", FreetextSectionFactory.create()],
    ])(
        "when a %s section is loaded, then it should display its title and description",
        (name, section) => {
            is_loading_sections = false;

            const wrapper = getWrapper(section);

            expect(wrapper.findComponent(SectionHeader).exists()).toBe(true);
            expect(wrapper.findComponent(SectionHeaderSkeleton).exists()).toBe(false);
            expect(wrapper.findComponent(SectionDescription).exists()).toBe(true);
        },
    );

    it("When a section is loading, Then it should display a skeleton section title", () => {
        is_loading_sections = true;

        const wrapper = getWrapper(skeleton_sections_collection[0].value);

        expect(wrapper.findComponent(SectionHeaderSkeleton).exists()).toBe(true);
        expect(wrapper.findComponent(SectionHeader).exists()).toBe(false);
    });

    it("When a freetext section is loaded, Then it should not display readonly fields", () => {
        is_loading_sections = false;

        const wrapper = getWrapper(FreetextSectionFactory.create());

        expect(wrapper.findComponent(ReadonlyFields).exists()).toBe(false);
    });

    it("When an artifact section is loaded, Then it should not display readonly fields if there is none ", () => {
        is_loading_sections = false;

        const wrapper = getWrapper(ArtifactSectionFactory.create());

        expect(wrapper.findComponent(ReadonlyFields).exists()).toBe(false);
    });

    it("When an artifact section is loaded, Then it should display readonly fields if any ", () => {
        is_loading_sections = false;

        const wrapper = getWrapper(
            ArtifactSectionFactory.override({
                fields: [
                    {
                        type: "text",
                        label: "Label",
                        value: "Value",
                        display_type: "column",
                    },
                ],
            }),
        );

        expect(wrapper.findComponent(ReadonlyFields).exists()).toBe(true);
    });
});
