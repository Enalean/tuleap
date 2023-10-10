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

import type { FeatureIdWithProgramIncrement } from "./drag-drop";
import type { ProgramIncrement } from "./ProgramIncrement/program-increment-retriever";
import * as featureExtractor from "./feature-extractor";
import type { Feature } from "../type";

describe("FeatureExtractor", () => {
    describe("extractFeatureIndexFromProgramIncrement", () => {
        it("When feature does not exist, Then error is thrown", () => {
            const feature_id_with_increment: FeatureIdWithProgramIncrement = {
                feature_id: 125,
                program_increment: {
                    id: 4,
                    features: [{ id: 14 } as Feature],
                } as ProgramIncrement,
            };

            expect(() =>
                featureExtractor.extractFeatureIndexFromProgramIncrement(feature_id_with_increment),
            ).toThrow("No feature with id #125 in program increment #4");
        });

        it("When feature exists, Then its index is returned", () => {
            const feature_id_with_increment: FeatureIdWithProgramIncrement = {
                feature_id: 15,
                program_increment: {
                    id: 4,
                    features: [{ id: 14 }, { id: 15 }, { id: 16 }] as Feature[],
                } as ProgramIncrement,
            };

            const index =
                featureExtractor.extractFeatureIndexFromProgramIncrement(feature_id_with_increment);

            expect(index).toBe(1);
        });
    });
});
