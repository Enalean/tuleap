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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import { formatOrderPositionForPatch } from "./order-position-for-patch-formatter";
import type { Feature } from "../type";
import type { FeaturePlanningChange } from "./feature-reordering";

describe("orderPositionForPatchFormatter", () => {
    it("When no order, Then null is returned", () => {
        const order_formatted = formatOrderPositionForPatch({
            order: null,
            feature: {} as Feature,
        });
        expect(order_formatted).toBeNull();
    });

    it("When order exists, Then order is formatted", () => {
        const order = {
            order: { direction: "after", compared_to: 666 },
            feature: { id: 777 },
        } as FeaturePlanningChange;

        const order_formatted = formatOrderPositionForPatch(order);
        expect(order_formatted).toEqual({ compared_to: 666, direction: "after", ids: [777] });
    });
});
