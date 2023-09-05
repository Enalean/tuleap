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

import type { TraceabilityMatrixTest } from "../../../../type";
import type { ArtifactFieldValueStatus } from "@tuleap/plugin-docgen-docx/src";

export function computeRequirementStatus(
    tests: ReadonlyArray<TraceabilityMatrixTest>,
): ArtifactFieldValueStatus {
    let nb_failed = 0;
    let nb_blocked = 0;
    let nb_not_run = 0;

    if (tests.length === 0) {
        return null;
    }

    for (const test of tests) {
        if (test.status === "failed") {
            nb_failed++;
        } else if (test.status === "blocked") {
            nb_blocked++;
        } else if (test.status === "notrun") {
            nb_not_run++;
        }
    }

    if (nb_failed > 0) {
        return "failed";
    } else if (nb_blocked > 0) {
        return "blocked";
    } else if (nb_not_run > 0) {
        return "notrun";
    }

    return "passed";
}
