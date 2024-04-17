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

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { mount } from "@vue/test-utils";
import EmptyState from "@/views/EmptyState.vue";
import { createGettext } from "vue3-gettext";
import DocumentContent from "@/components/DocumentContent.vue";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";
import TableOfContents from "@/components/TableOfContents.vue";
import NoAccessState from "@/views/NoAccessState.vue";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import type { ComponentPublicInstance } from "vue";
import DocumentView from "@/views/DocumentView.vue";
import { buildVueDompurifyHTMLDirective } from "vue-dompurify-html";

describe("DocumentView", () => {
    describe("when sections not found", () => {
        it("should display empty state view", () => {
            const wrapper = getWrapperWithProps({ sections: [] });

            expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentContent).exists()).toBe(false);
        });
    });

    describe("when sections found", () => {
        it("should display document content view", () => {
            const wrapper = getWrapperWithProps({ sections: [ArtidocSectionFactory.create()] });

            expect(wrapper.findComponent(DocumentContent).exists()).toBe(true);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
            expect(wrapper.findComponent(NoAccessState).exists()).toBe(false);
        });
        it("should display table of contents", () => {
            const wrapper = getWrapperWithProps({ sections: [ArtidocSectionFactory.create()] });

            expect(wrapper.findComponent(TableOfContents).exists()).toBe(true);
        });
    });

    describe("when the user is not allowed to access the document", () => {
        it("should display no access state view", () => {
            const wrapper = getWrapperWithProps({ sections: undefined });

            expect(wrapper.findComponent(NoAccessState).exists()).toBe(true);
            expect(wrapper.findComponent(DocumentContent).exists()).toBe(false);
            expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
        });
    });
});

function getWrapperWithProps(props: {
    sections: readonly ArtidocSection[] | undefined;
}): VueWrapper<ComponentPublicInstance> {
    return mount(DocumentView, {
        global: {
            plugins: [createGettext({ silent: true })],
            stubs: ["table-of-contents", "document-content", "empty-state", "no-access-state"],
            directives: {
                "dompurify-html": buildVueDompurifyHTMLDirective(),
            },
        },
        props,
    });
}
