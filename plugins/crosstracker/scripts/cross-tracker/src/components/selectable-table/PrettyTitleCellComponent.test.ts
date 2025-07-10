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

import { beforeEach, describe, expect, it } from "vitest";
import { nextTick } from "vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { ArtifactLinkDirection } from "../../domain/ArtifactsTable";
import { FORWARD_DIRECTION, PRETTY_TITLE_CELL } from "../../domain/ArtifactsTable";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { CAN_DISPLAY_ARTIFACT_LINK } from "../../injection-symbols";
import PrettyTitleCellComponent from "./PrettyTitleCellComponent.vue";
import CaretIndentation from "./CaretIndentation.vue";
import ArtifactLinkArrow from "./ArtifactLinkArrow.vue";

describe("PrettyTitleCellComponent", () => {
    let artifact_uri: string;
    let can_display_artifact_link: boolean;
    let expected_number_of_forward_link: number;
    let expected_number_of_reverse_link: number;
    let parent_element: HTMLElement | undefined;
    let parent_caret: HTMLElement | undefined;
    let direction: ArtifactLinkDirection | undefined;
    let reverse_links_count: number | undefined;

    beforeEach(() => {
        artifact_uri = "/plugins/tracker/?aid=286";
        can_display_artifact_link = true;
        expected_number_of_forward_link = 0;
        expected_number_of_reverse_link = 0;
        parent_element = undefined;
        parent_caret = undefined;
        direction = undefined;
        reverse_links_count = undefined;
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
                expected_number_of_forward_link,
                expected_number_of_reverse_link,
                level: 0,
                is_last: false,
                parent_element,
                parent_caret,
                reverse_links_count,
                direction,
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

    describe("Button", () => {
        it("should hide the button, when artifact has no links ", () => {
            const wrapper = getWrapper();

            expect(
                wrapper.find("[data-test=pretty-title-links-button]").attributes("aria-hidden"),
            ).toBe("true");
        });

        it("should not hide the button, when artifact has links", () => {
            expected_number_of_forward_link = 2;
            expected_number_of_reverse_link = 1;
            const wrapper = getWrapper();

            expect(
                wrapper.find("[data-test=pretty-title-links-button]").attributes("aria-hidden"),
            ).toBe("false");
        });
    });

    describe("Caret display", () => {
        const caret_right = "fa-caret-right";
        const caret_down = "fa-caret-down";

        it("should display a caret down, when caret is clicked", async () => {
            expected_number_of_forward_link = 2;
            const wrapper = getWrapper();

            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_right);
            await wrapper.find("[data-test=pretty-title-caret]").trigger("click");
            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_down);
        });

        it("should display a caret right, when caret is clicked again", async () => {
            expected_number_of_reverse_link = 1;
            const wrapper = getWrapper();

            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_right);
            await wrapper.find("[data-test=pretty-title-caret]").trigger("click");
            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_down);
            await wrapper.find("[data-test=pretty-title-caret]").trigger("click");
            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_right);
        });
    });

    describe("Caret indentation", () => {
        it("should display a Caret Indentation component with the same level", () => {
            expected_number_of_forward_link = 2;
            const wrapper = getWrapper();

            expect(wrapper.findComponent(CaretIndentation).props("level")).toBe(
                wrapper.props("level"),
            );
        });
    });

    describe("ArtifactLinkArrow", () => {
        it("should not include an ArtifactLinkArrow if there are no parent elements", () => {
            const wrapper = getWrapper();

            expect(wrapper.findComponent(ArtifactLinkArrow).exists()).toBe(false);
        });

        it("should include an ArtifactLinkArrow if there are parent elements", async () => {
            parent_element = {} as HTMLElement;
            parent_caret = {} as HTMLElement;
            direction = FORWARD_DIRECTION;
            reverse_links_count = 3;

            const wrapper = getWrapper();

            await nextTick();

            expect(wrapper.findComponent(ArtifactLinkArrow).exists()).toBe(true);
        });

        it("should include an ArtifactLinkArrow if there are parent elements BUT no reverse links", async () => {
            parent_element = {} as HTMLElement;
            parent_caret = {} as HTMLElement;
            direction = FORWARD_DIRECTION;
            reverse_links_count = 0;

            const wrapper = getWrapper();

            await nextTick();

            expect(wrapper.findComponent(ArtifactLinkArrow).exists()).toBe(true);
        });
    });
});
