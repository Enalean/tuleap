/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { patch, put } from "tlp";
import type { FeatureToPlan } from "../../drag-drop";
import type { FeaturePlanningChangeInProgramIncrement } from "../../feature-reordering";
import { formatOrderPositionForPatch } from "../../order-position-for-patch-formatter";

export async function planElementInProgramIncrement(
    feature_id: number,
    feature_artifact_link_id: number,
    element_to_plan: Array<FeatureToPlan>
): Promise<void> {
    await put(`/api/v1/artifacts/${encodeURIComponent(feature_id)}`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            values: [{ field_id: feature_artifact_link_id, links: element_to_plan }],
            comment: { body: "", format: "text" },
        }),
    });
}
export async function reorderElementInProgramIncrement(
    feature_position: FeaturePlanningChangeInProgramIncrement
): Promise<void> {
    const order_format = formatOrderPositionForPatch(feature_position);

    if (!order_format) {
        throw new Error(
            "Cannot reorder element #" +
                feature_position.feature.id +
                " in program increment #" +
                feature_position.program_increment_id +
                " because order is null"
        );
    }

    await patch(
        `/api/v1/program_increment/${encodeURIComponent(
            feature_position.program_increment_id
        )}/content`,
        {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                add: [],
                order: order_format,
            }),
        }
    );
}
