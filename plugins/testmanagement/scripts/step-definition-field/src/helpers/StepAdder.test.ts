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
import { addStep } from "./StepAdder";

const empty_step: Step = {
    id: 0,
    raw_description: "Empty step",
} as Step;
const step_1 = {
    id: 1,
    raw_description: "Step 1 description",
    is_deleted: false,
} as Step;
const step_collection = ref([step_1]);

describe("StepAdder", () => {
    it("should insert a step at the given index, sets uuid and is_deleted", () => {
        addStep(step_collection, 1, empty_step);

        expect(step_collection.value).toHaveLength(2);
        expect(step_collection.value[1].raw_description).toStrictEqual(empty_step.raw_description);
        expect(step_collection.value[1].is_deleted).toBe(false);
        expect(step_collection.value[1].uuid).toBeDefined();
    });
});
