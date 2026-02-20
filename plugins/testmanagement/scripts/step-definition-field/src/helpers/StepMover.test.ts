/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
import type { Step } from "../Step";
import { moveStep } from "./StepMover";

const step_a = {
    id: 1,
    uuid: "uuid_a",
} as Step;
const step_b = {
    id: 2,
    uuid: "uuid_b",
} as Step;
const step_collection = ref([step_a, step_b]);

describe("StepMover", () => {
    beforeEach(() => {
        step_collection.value = [step_a, step_b];
    });

    it("should move a step to the first place", () => {
        moveStep(step_collection, step_b, 0);

        expect(step_collection.value).toHaveLength(2);
        expect(step_collection.value[0]).toStrictEqual(step_b);
        expect(step_collection.value[1]).toStrictEqual(step_a);
    });

    it("should not move a step if it is already at the first place", () => {
        moveStep(step_collection, step_a, 0);

        expect(step_collection.value).toHaveLength(2);
        expect(step_collection.value[0]).toStrictEqual(step_a);
        expect(step_collection.value[1]).toStrictEqual(step_b);
    });

    it("should not move a step at the last place", () => {
        moveStep(step_collection, step_a, 1);

        expect(step_collection.value).toHaveLength(2);
        expect(step_collection.value[0]).toStrictEqual(step_b);
        expect(step_collection.value[1]).toStrictEqual(step_a);
    });
});
