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
import { setStepDeleted } from "./StepRemover";

let step_a = {
    id: 1,
    uuid: "uuid_a",
} as Step;
let step_b = {
    id: 2,
    uuid: "uuid_b",
} as Step;
const step_collection = ref([step_a, step_b]);

describe("StepRemover", () => {
    beforeEach(() => {
        step_a = {
            is_deleted: false,
        } as Step;
        step_b = {
            is_deleted: true,
        } as Step;
        step_collection.value = [step_a, step_b];
    });

    it("should mark a step as deleted", () => {
        setStepDeleted(step_collection, step_a, true);

        expect(step_collection.value).toHaveLength(2);
        expect(step_collection.value[0].is_deleted).toBe(true);
        expect(step_collection.value[1].is_deleted).toBe(true);
    });

    it("should not mark as deleted", () => {
        setStepDeleted(step_collection, step_b, false);

        expect(step_collection.value).toHaveLength(2);
        expect(step_collection.value[0].is_deleted).toBe(false);
        expect(step_collection.value[1].is_deleted).toBe(false);
    });
});
