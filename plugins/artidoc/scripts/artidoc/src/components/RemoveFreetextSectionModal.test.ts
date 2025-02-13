/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { describe, it, expect, beforeEach } from "vitest";
import { createGettext } from "vue3-gettext";
import type { SectionsCollection, StoredArtidocSection } from "@/sections/SectionsCollection";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import {
    REMOVE_FREETEXT_SECTION_MODAL,
    useRemoveFreetextSectionModal,
} from "@/composables/useRemoveFreetextSectionModal";
import type { UseRemoveFreetextSectionModal } from "@/composables/useRemoveFreetextSectionModal";
import RemoveFreetextSectionModal from "@/components/RemoveFreetextSectionModal.vue";
import { SET_GLOBAL_ERROR_MESSAGE } from "@/global-error-message-injection-key";
import { noop } from "@/helpers/noop";
import type { RemoveSections } from "@/sections/remove/SectionsRemover";
import { SectionsRemoverStub } from "@/sections/stubs/SectionsRemoverStub";
import { CreateStoredSections } from "@/sections/states/CreateStoredSections";

describe("RemoveFreetextSectionModal", () => {
    let freetext_section: StoredArtidocSection,
        sections_collection: SectionsCollection,
        bus: UseRemoveFreetextSectionModal;

    beforeEach(() => {
        freetext_section = CreateStoredSections.fromArtidocSection(FreetextSectionFactory.create());
        sections_collection = SectionsCollectionStub.withSections([freetext_section]);

        bus = useRemoveFreetextSectionModal();
    });

    const getWrapper = (remove_sections: RemoveSections): VueWrapper =>
        shallowMount(RemoveFreetextSectionModal, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [CONFIGURATION_STORE.valueOf()]:
                        ConfigurationStoreStub.withSelectedTracker(null),
                    [SECTIONS_COLLECTION.valueOf()]: sections_collection,
                    [REMOVE_FREETEXT_SECTION_MODAL.valueOf()]: bus,
                    [SET_GLOBAL_ERROR_MESSAGE.valueOf()]: noop,
                },
            },
            props: {
                remove_sections,
            },
        });

    it("When the user confirms the removal of the section, then it should delete it", () => {
        const sections_remover = SectionsRemoverStub.withExpectedCall();
        const wrapper = getWrapper(sections_remover);
        expect(wrapper.classes()).not.toContain("tlp-modal-shown");

        bus.openModal(freetext_section);
        expect(wrapper.classes()).toContain("tlp-modal-shown");

        wrapper.find("[data-test=remove-button]").trigger("click");

        expect(sections_remover.getLastRemovedSection()).toStrictEqual(freetext_section);
    });

    it("When the user cancels the removal of the section, then it should not delete it ", () => {
        const wrapper = getWrapper(SectionsRemoverStub.withNoExpectedCall());

        bus.openModal(freetext_section);
        wrapper.find("[data-test=cancel-button]").trigger("click");
    });
});
