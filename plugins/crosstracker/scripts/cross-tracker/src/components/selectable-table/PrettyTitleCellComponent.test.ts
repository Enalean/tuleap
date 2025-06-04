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

import { describe, it, beforeEach, expect } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { PRETTY_TITLE_CELL } from "../../domain/ArtifactsTable";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { CAN_DISPLAY_ARTIFACT_LINK } from "../../injection-symbols";
import PrettyTitleCellComponent from "./PrettyTitleCellComponent.vue";

describe("PrettyTitleCellComponent", () => {
    let artifact_uri: string;
    let can_display_artifact_link: boolean;
    let number_of_forward_link: number;
    let number_of_reverse_link: number;

    beforeEach(() => {
        artifact_uri = "/plugins/tracker/?aid=286";
        can_display_artifact_link = true;
        number_of_forward_link = 0;
        number_of_reverse_link = 0;
    });

    const getWrapper = (): VueWrapper<InstanceType<typeof PrettyTitleCellComponent>> => {
        return shallowMount(PrettyTitleCellComponent, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [CAN_DISPLAY_ARTIFACT_LINK.valueOf()]: can_display_artifact_link,
                },
            },
            props: {
                cell: {
                    type: PRETTY_TITLE_CELL,
                    title: "uncensorable litigant",
                    tracker_name: "story",
                    artifact_id: 76,
                    color: "coral-pink",
                },
                artifact_uri,
                number_of_forward_link,
                number_of_reverse_link,
            },
        });
    };

    it("when the cell is a pretty title, it renders a link to artifact URI", () => {
        artifact_uri = "/plugins/tracker/?aid=76";
        const wrapper = getWrapper();

        expect(wrapper.get("a").attributes("href")).toBe(artifact_uri);
    });

    describe("Feature flag", () => {
        it("when the feature flag is enabled, it should renders artifact link", () => {
            const wrapper = getWrapper();

            expect(wrapper.find("[data-test=pretty-title-caret]").exists()).toBe(true);
        });

        it("when the feature flag is disabled, it should NOT renders artifact link", () => {
            can_display_artifact_link = false;
            const wrapper = getWrapper();

            expect(wrapper.find("[data-test=pretty-title-caret]").exists()).toBe(false);
        });
    });

    describe("Caret display", () => {
        it("should hide the caret, when artifact has no links ", () => {
            const wrapper = getWrapper();

            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(
                "hidden-caret",
            );
        });

        it("should not hide the caret, when artifact has links", () => {
            number_of_forward_link = 2;
            number_of_reverse_link = 1;
            const wrapper = getWrapper();

            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).not.toContain(
                "hidden-caret",
            );
        });
    });
});
