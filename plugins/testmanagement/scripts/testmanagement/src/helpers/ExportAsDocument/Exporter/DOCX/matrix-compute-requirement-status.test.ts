/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { computeRequirementStatus } from "./matrix-compute-requirement-status";
import type { TraceabilityMatrixTest } from "../../../../type";

describe("matrix-compute-requirement-status", () => {
    describe("computeRequirementStatus", () => {
        it("should return null if there is not tests linked to the requirement", () => {
            const result = computeRequirementStatus([]);

            expect(result).toBeNull();
        });

        it("should return 'failed' if there is at least one linked test to the requirement set to 'failed'", () => {
            const result = computeRequirementStatus([
                {
                    status: "passed",
                } as TraceabilityMatrixTest,
                {
                    status: "failed",
                } as TraceabilityMatrixTest,
                {
                    status: "blocked",
                } as TraceabilityMatrixTest,
                {
                    status: "notrun",
                } as TraceabilityMatrixTest,
            ]);

            expect(result).toBe("failed");
        });

        it("should return 'blocked' if there is at least one linked test to the requirement set to 'blocked' and no test set to 'failed'", () => {
            const result = computeRequirementStatus([
                {
                    status: "passed",
                } as TraceabilityMatrixTest,
                {
                    status: "blocked",
                } as TraceabilityMatrixTest,
                {
                    status: "notrun",
                } as TraceabilityMatrixTest,
            ]);

            expect(result).toBe("blocked");
        });

        it("should return 'notrun' if there is at least one linked test to the requirement set to 'notrun' and no test set to 'failed' or 'blocked'", () => {
            const result = computeRequirementStatus([
                {
                    status: "passed",
                } as TraceabilityMatrixTest,
                {
                    status: "passed",
                } as TraceabilityMatrixTest,
                {
                    status: "notrun",
                } as TraceabilityMatrixTest,
            ]);

            expect(result).toBe("notrun");
        });

        it("should return 'passed' if there all linked test to the requirement are set to 'passed'", () => {
            const result = computeRequirementStatus([
                {
                    status: "passed",
                } as TraceabilityMatrixTest,
                {
                    status: "passed",
                } as TraceabilityMatrixTest,
            ]);

            expect(result).toBe("passed");
        });
    });
});
