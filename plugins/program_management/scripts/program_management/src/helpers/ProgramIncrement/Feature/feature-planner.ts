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

import { patch } from "@tuleap/tlp-fetch";
import type { FeaturePlanningChangeInProgramIncrement } from "../../feature-reordering";
import { formatOrderPositionForPatch } from "../../order-position-for-patch-formatter";

export async function planElementInProgramIncrement(
    feature_position: FeaturePlanningChangeInProgramIncrement,
): Promise<void> {
    await patch(
        `/api/v1/program_increment/${encodeURIComponent(
            feature_position.to_program_increment_id,
        )}/content`,
        {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                add: [{ id: feature_position.feature.id }],
                order: formatOrderPositionForPatch(feature_position),
            }),
        },
    );
}
export async function reorderElementInProgramIncrement(
    feature_position: FeaturePlanningChangeInProgramIncrement,
): Promise<void> {
    const order_format = formatOrderPositionForPatch(feature_position);

    if (!order_format) {
        throw new Error(
            "Cannot reorder element #" +
                feature_position.feature.id +
                " in program increment #" +
                feature_position.to_program_increment_id +
                " because order is null",
        );
    }

    await patch(
        `/api/v1/program_increment/${encodeURIComponent(
            feature_position.to_program_increment_id,
        )}/content`,
        {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                add: [],
                order: order_format,
            }),
        },
    );
}
