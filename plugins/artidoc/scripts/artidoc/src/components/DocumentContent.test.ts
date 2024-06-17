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
import type { ComponentPublicInstance } from "vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DocumentContent from "@/components/DocumentContent.vue";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import SectionContainer from "@/components/SectionContainer.vue";
import * as sectionsStore from "@/stores/useSectionsStore";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import AddNewSectionButton from "./AddNewSectionButton.vue";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";

describe("DocumentContent", () => {
    function getWrapper(): VueWrapper<ComponentPublicInstance> {
        const default_artifact_section = ArtifactSectionFactory.create();
        const default_pending_artifact_section = PendingArtifactSectionFactory.create();

        vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
            InjectedSectionsStoreStub.withLoadedSections([
                ArtifactSectionFactory.override({
                    title: { ...default_artifact_section.title, value: "Title 1" },
                    artifact: { ...default_artifact_section.artifact, id: 1 },
                }),
                ArtifactSectionFactory.override({
                    title: { ...default_artifact_section.title, value: "Title 2" },
                    artifact: { ...default_artifact_section.artifact, id: 2 },
                }),
                PendingArtifactSectionFactory.override({
                    title: { ...default_pending_artifact_section.title, value: "Title 3" },
                }),
            ]),
        );

        return shallowMount(DocumentContent);
    }

    it("should display the two sections", () => {
        mockStrictInject([[CAN_USER_EDIT_DOCUMENT, false]]);
        const list = getWrapper().find("ol");
        expect(list.findAllComponents(SectionContainer)).toHaveLength(3);
    });

    it("sections should have an id for anchor feature except pending artifact section", () => {
        mockStrictInject([[CAN_USER_EDIT_DOCUMENT, false]]);
        const list = getWrapper().find("ol");
        const sections = list.findAll("li");
        expect(sections[0].attributes().id).toBe("1");
        expect(sections[1].attributes().id).toBe("2");
        expect(sections[2].attributes().id).toBe("");
    });

    it("should not display add new section button if user cannot edit the document", () => {
        mockStrictInject([[CAN_USER_EDIT_DOCUMENT, false]]);
        expect(getWrapper().findAllComponents(AddNewSectionButton)).toHaveLength(0);
    });

    it("should display n+1 add new section button if user can edit the document", () => {
        mockStrictInject([[CAN_USER_EDIT_DOCUMENT, true]]);
        expect(getWrapper().findAllComponents(AddNewSectionButton)).toHaveLength(4);
    });
});
