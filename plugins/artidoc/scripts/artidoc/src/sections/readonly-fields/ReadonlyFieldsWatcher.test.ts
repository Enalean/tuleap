/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { ref } from "vue";
import type { Ref } from "vue";
import { describe, vi, it, expect, beforeEach } from "vitest";
import type { MockInstance } from "vitest";
import { okAsync } from "neverthrow";
import { flushPromises } from "@vue/test-utils";
import * as rest_querier from "@/helpers/rest-querier";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import { ConfigurationFieldStub } from "@/sections/stubs/ConfigurationFieldStub";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import type { SectionsCollection } from "@/sections/SectionsCollection";
import { watchUpdateSectionsReadonlyFields } from "@/sections/readonly-fields/ReadonlyFieldsWatcher";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import * as section_loader from "@/sections/SectionsLoader";

describe("ReadonlyFieldsWatcher", () => {
    const artidoc_id = 123;
    const artidoc_sections = [
        ArtifactSectionFactory.create(),
        ArtifactSectionFactory.create(),
        FreetextSectionFactory.create(),
    ];

    let sections_collection: SectionsCollection,
        selected_fields: Ref<ConfigurationField[]>,
        load_sections: MockInstance;

    beforeEach(() => {
        sections_collection = SectionsCollectionStub.withSections(artidoc_sections);
        selected_fields = ConfigurationStoreStub.withSelectedFields([]).selected_fields;

        load_sections = vi.spyOn(section_loader, "getSectionsLoader");
        watchUpdateSectionsReadonlyFields(
            sections_collection,
            selected_fields,
            artidoc_id,
            ref(false),
            ref(false),
        );
    });

    it("should reload the sections when readonly fields change", async () => {
        vi.spyOn(rest_querier, "getAllSections").mockReturnValue(okAsync(artidoc_sections));

        selected_fields.value = [ConfigurationFieldStub.build()];

        await flushPromises();
        expect(load_sections).toHaveBeenCalledOnce();
    });
});
