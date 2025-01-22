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
import { shallowMount } from "@vue/test-utils";
import App from "@/App.vue";
import DocumentView from "@/views/DocumentView.vue";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { SECTIONS_COLLECTION } from "@/sections/sections-collection-injection-key";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import * as rest_querier from "./helpers/rest-querier";

describe("App", () => {
    it("should load and display the document view", () => {
        const getAllJSON = vi.spyOn(rest_querier, "getAllSections");
        const wrapper = shallowMount(App, {
            global: {
                provide: {
                    [CONFIGURATION_STORE.valueOf()]:
                        ConfigurationStoreStub.withoutAllowedTrackers(),
                    [CAN_USER_EDIT_DOCUMENT.valueOf()]: true,
                    [DOCUMENT_ID.valueOf()]: 1,
                    [SECTIONS_COLLECTION.valueOf()]: SectionsCollectionStub.withSections([]),
                },
            },
        });

        expect(getAllJSON).toHaveBeenCalledOnce();
        expect(wrapper.findComponent(DocumentView).exists()).toBe(true);
    });
});
