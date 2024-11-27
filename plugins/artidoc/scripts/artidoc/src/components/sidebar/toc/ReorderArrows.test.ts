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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import ReorderArrows from "@/components/sidebar/toc/ReorderArrows.vue";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { injectInternalId } from "@/helpers/inject-internal-id";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import { promised_noop } from "@/helpers/noop";
import type { SectionsStore } from "@/stores/useSectionsStore";
import { DOCUMENT_ID } from "@/document-id-injection-key";

describe("ReorderArrows", () => {
    function getWrapper(
        { is_first, is_last }: { is_first: boolean; is_last: boolean },
        up: SectionsStore["moveSectionUp"],
        down: SectionsStore["moveSectionDown"],
    ): VueWrapper {
        return shallowMount(ReorderArrows, {
            props: {
                is_first,
                is_last,
                section: injectInternalId(ArtifactSectionFactory.create()),
            },
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [DOCUMENT_ID.valueOf()]: 123,
                    [SECTIONS_STORE.valueOf()]: InjectedSectionsStoreStub.withMockedMoveSection(
                        up,
                        down,
                    ),
                },
            },
        });
    }

    it("should display two move buttons for a section", async () => {
        const up = vi.fn();
        const down = vi.fn();

        const wrapper = getWrapper({ is_first: false, is_last: false }, up, down);

        const up_button = wrapper.find("[data-test=move-up]");
        const down_button = wrapper.find("[data-test=move-down]");

        expect(up_button.exists()).toBe(true);
        expect(down_button.exists()).toBe(true);

        up.mockResolvedValue(true);
        await up_button.trigger("click");
        expect(up).toHaveBeenCalled();
        expect(down).not.toHaveBeenCalled();

        up.mockReset();
        down.mockReset();

        down.mockResolvedValue(true);
        await down_button.trigger("click");
        expect(up).not.toHaveBeenCalled();
        expect(down).toHaveBeenCalled();
    });

    it("should display one move button for the first section", () => {
        const wrapper = getWrapper(
            { is_first: true, is_last: false },
            promised_noop,
            promised_noop,
        );

        expect(wrapper.find("[data-test=move-up]").exists()).toBe(false);
        expect(wrapper.find("[data-test=move-down]").exists()).toBe(true);
    });

    it("should display one move button for the last section", () => {
        const wrapper = getWrapper(
            { is_first: false, is_last: true },
            promised_noop,
            promised_noop,
        );

        expect(wrapper.find("[data-test=move-up]").exists()).toBe(true);
        expect(wrapper.find("[data-test=move-down]").exists()).toBe(false);
    });

    it("should NOT display any move button when there is only one section", () => {
        const wrapper = getWrapper({ is_first: true, is_last: true }, promised_noop, promised_noop);

        expect(wrapper.find("[data-test=move-up]").exists()).toBe(false);
        expect(wrapper.find("[data-test=move-down]").exists()).toBe(false);
    });
});
