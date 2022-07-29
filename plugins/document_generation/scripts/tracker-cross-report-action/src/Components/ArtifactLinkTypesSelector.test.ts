/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import { flushPromises, shallowMount } from "@vue/test-utils";
import ArtifactLinkTypesSelector from "./ArtifactLinkTypesSelector.vue";
import { getGlobalTestOptions } from "./global-options-for-test";
import * as rest_querier from "../rest-querier";
import type { TrackerUsedArtifactLinkResponse } from "@tuleap/plugin-tracker-rest-api-types/src";

describe("ArtifactLinkTypesSelector", () => {
    it("displays possible reports and select them all by default", async () => {
        vi.spyOn(rest_querier, "getTrackerCurrentlyUsedArtifactLinkTypes").mockResolvedValue([
            { shortname: "shortname_a", forward_label: "A" },
            { shortname: "shortname_b", forward_label: "B" },
        ] as TrackerUsedArtifactLinkResponse[]);

        const wrapper = shallowMount(ArtifactLinkTypesSelector, {
            global: getGlobalTestOptions(),
            props: {
                tracker_id: 21,
                artifact_link_types: [],
            },
        });

        await flushPromises();

        const emitted_input = wrapper.emitted("update:artifact_link_types");
        expect(emitted_input).toBeDefined();
        if (emitted_input === undefined) {
            throw new Error("Expected an update event to be emitted");
        }
        expect(emitted_input[0]).toStrictEqual([["shortname_a", "shortname_b"]]);
    });

    it("disables the selector when no tracker ID is provided", () => {
        const wrapper = shallowMount(ArtifactLinkTypesSelector, {
            global: getGlobalTestOptions(),
            props: {
                tracker_id: null,
                artifact_link_types: [],
            },
        });

        expect(wrapper.find(".tlp-form-element-disabled").exists()).toBe(true);
    });
});
