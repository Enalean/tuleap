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
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import DocumentContent from "@/components/DocumentContent.vue";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import AddNewSectionButton from "./AddNewSectionButton.vue";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import EditorToolbar from "@/components/toolbar/EditorToolbar.vue";
import { SECTIONS_STATES_COLLECTION } from "@/sections/states/sections-states-collection-injection-key";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";

describe("DocumentContent", () => {
    let sections_collection: SectionsCollection,
        section_1: ArtidocSection,
        section_2: ArtidocSection,
        section_3: ArtidocSection,
        can_user_edit_document: boolean;

    const getWrapper = (): VueWrapper =>
        shallowMount(DocumentContent, {
            global: {
                provide: {
                    [SECTIONS_COLLECTION.valueOf()]: sections_collection,
                    [SECTIONS_STATES_COLLECTION.valueOf()]: SectionsStatesCollectionStub.build(),
                    [CAN_USER_EDIT_DOCUMENT.valueOf()]: can_user_edit_document,
                },
            },
        });

    beforeEach(() => {
        const default_artifact_section = ArtifactSectionFactory.create();
        section_1 = ArtifactSectionFactory.override({
            title: "Title 1",
            artifact: { ...default_artifact_section.artifact, id: 1 },
            level: 1,
        });
        section_2 = ArtifactSectionFactory.override({
            title: "Title 2",
            artifact: { ...default_artifact_section.artifact, id: 2 },
            level: 2,
        });
        section_3 = PendingArtifactSectionFactory.override({
            title: "Title 3",
            level: 3,
        });

        sections_collection = SectionsCollectionStub.withSections([
            section_1,
            section_2,
            section_3,
        ]);

        can_user_edit_document = true;
    });

    it("should display the three sections", () => {
        const sections = getWrapper().findAll("[data-test=artidoc-section]");
        expect(sections).toHaveLength(3);
    });

    it("sections should have an id for anchor feature except pending artifact section", () => {
        const sections = getWrapper().findAll("[data-test=artidoc-section]");
        expect(sections[0].attributes().id).toBe(`section-${section_1.id}`);
        expect(sections[1].attributes().id).toBe(`section-${section_2.id}`);
        expect(sections[2].attributes().id).toBe(`section-${section_3.id}`);
    });

    it("should not display add new section button if user cannot edit the document", () => {
        can_user_edit_document = false;

        expect(getWrapper().findAllComponents(AddNewSectionButton)).toHaveLength(0);
    });

    it("should display n+1 add new section button if user can edit the document", () => {
        expect(getWrapper().findAllComponents(AddNewSectionButton)).toHaveLength(4);
    });

    it("should display the mono-toolbar when the user can edit the document", () => {
        expect(getWrapper().findComponent(EditorToolbar).exists()).toBe(true);
    });

    it("should not display the mono-toolbar when the user cannot edit the document", () => {
        can_user_edit_document = false;

        expect(getWrapper().findComponent(EditorToolbar).exists()).toBe(false);
    });
});
