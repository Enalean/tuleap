/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
import type { ArtifactLinkDirection, LinkTypeCell } from "../../domain/ArtifactsTable";
import { FORWARD_DIRECTION, REVERSE_DIRECTION, LINK_TYPE_CELL } from "../../domain/ArtifactsTable";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import LinkTypeCellComponent from "./LinkTypeCellComponent.vue";

describe("LinkTypeCellComponent", () => {
    const getWrapper = (
        cell: LinkTypeCell,
    ): VueWrapper<InstanceType<typeof LinkTypeCellComponent>> => {
        return shallowMount(LinkTypeCellComponent, {
            global: { ...getGlobalTestOptions() },
            props: { cell },
        });
    };
    it.each<[ArtifactLinkDirection, string]>([
        [FORWARD_DIRECTION, "tlp-badge-firemist-silver"],
        [REVERSE_DIRECTION, "tlp-badge-inca-silver"],
    ])(
        "When the link is in %s direction, it displays the %s outlined color badge",
        (link_type: ArtifactLinkDirection, badge_color: string) => {
            const wrapper = getWrapper({
                type: LINK_TYPE_CELL,
                direction: link_type,
                label: "Covered By",
            });

            expect(wrapper.find("[data-test=link-type-cell-label]").classes()).toContain(
                badge_color,
            );
        },
    );
});
