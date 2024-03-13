/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
import { describe, beforeEach, it, expect, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import * as strict_inject from "@tuleap/vue-strict-inject";
import {
    injected_show_pull_requests_related_to_me,
    StubInjectionSymbols,
} from "../../../../tests/injection-symbols-stub";
import { getGlobalTestOptions } from "../../../../tests/global-options-for-tests";
import { AuthorFilterStub } from "../../../../tests/stubs/AuthorFilterStub";
import { UserStub } from "../../../../tests/stubs/UserStub";
import { GettextStub } from "../../../../tests/stubs/GettextStub";
import { ReviewerFilterBuilder } from "../Reviewer/ReviewerFilter";
import { ListFiltersStore } from "../ListFiltersStore";
import type { StoreListFilters } from "../ListFiltersStore";
import RelatedToMeSwitch from "./RelatedToMeSwitch.vue";

describe("RelatedToMeSwitch", () => {
    let filters_store: StoreListFilters;

    beforeEach(() => {
        filters_store = ListFiltersStore(ref([]));
    });

    const getWrapper = (): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation(
            StubInjectionSymbols.withDefaults(),
        );

        return shallowMount(RelatedToMeSwitch, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: { filters_store },
        });
    };

    it("When the switch value changes, then it should update the value of SHOW_PULL_REQUESTS_RELATED_TO_ME accordingly", async () => {
        const related_to_me_switch = getWrapper().find("[data-test=related-to-me-switch]");

        await related_to_me_switch.setValue(true);
        expect(injected_show_pull_requests_related_to_me.value).toBe(true);

        await related_to_me_switch.setValue(false);
        expect(injected_show_pull_requests_related_to_me.value).toBe(false);
    });

    it('should be disabled when there is an "author" filter defined', async () => {
        const wrapper = getWrapper();

        filters_store.storeFilter(
            AuthorFilterStub.fromAuthor(UserStub.withIdAndName(102, "John Doe")),
        );

        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=related-to-me-switch]").attributes("disabled"),
        ).toBeDefined();
        expect(wrapper.find("[data-test=related-to-me-switch-label]").classes()).toContain(
            "disabled",
        );
    });

    it('should be disabled when there is a "reviewer" filter defined', async () => {
        const wrapper = getWrapper();

        filters_store.storeFilter(
            ReviewerFilterBuilder(GettextStub).fromReviewer(
                UserStub.withIdAndName(102, "John Doe"),
            ),
        );

        await wrapper.vm.$nextTick();

        expect(
            wrapper.find("[data-test=related-to-me-switch]").attributes("disabled"),
        ).toBeDefined();
        expect(wrapper.find("[data-test=related-to-me-switch-label]").classes()).toContain(
            "disabled",
        );
    });
});
