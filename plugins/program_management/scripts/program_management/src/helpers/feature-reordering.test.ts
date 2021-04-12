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

import { getFeatureReorderPosition } from "./feature-reordering";
import type { Feature } from "../type";

describe("Feature Reordering", () => {
    describe("getFeatureReorderPosition", () => {
        it("When sibling is null, Then we get a position after the last feature of the list", () => {
            const feature: Feature = { id: 115 } as Feature;
            const backlog = [feature, { id: 116 }, { id: 117 }] as Feature[];
            const position = getFeatureReorderPosition(feature, null, backlog);

            expect(position).toEqual({ ids: [115], direction: "after", compared_to: 117 });
        });

        it("When feature is moving between 2 features, Then we get a position after the first feature", () => {
            const feature: Feature = { id: 115 } as Feature;
            const sibling: Feature = { id: 117 } as Feature;
            const backlog = [feature, { id: 116 }, sibling] as Feature[];
            const position = getFeatureReorderPosition(feature, sibling, backlog);

            expect(position).toEqual({ ids: [115], direction: "after", compared_to: 116 });
        });

        it("When feature is moving at the first place, Then we get a position before the first feature", () => {
            const feature: Feature = { id: 115 } as Feature;
            const sibling: Feature = { id: 116 } as Feature;
            const backlog = [sibling, { id: 111 }, feature] as Feature[];
            const position = getFeatureReorderPosition(feature, sibling, backlog);

            expect(position).toEqual({ ids: [115], direction: "before", compared_to: 116 });
        });

        it("When sibling does not exist in the backlog, Then error is thrown", () => {
            const feature: Feature = { id: 115 } as Feature;
            const sibling: Feature = { id: 666 } as Feature;
            const backlog = [feature] as Feature[];
            expect(() => getFeatureReorderPosition(feature, sibling, backlog)).toThrowError(
                "Cannot find feature with id #666 in program backlog."
            );
        });
    });
});
