/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { BacklogItem } from "../../type";

export interface TestStats {
    passed: number;
    failed: number;
    blocked: number;
    notrun: number;
}

export function computeTestStats(backlog_item: BacklogItem): TestStats {
    const stats: TestStats = {
        passed: 0,
        failed: 0,
        blocked: 0,
        notrun: 0,
    };

    for (const test_definition of backlog_item.test_definitions) {
        const status = test_definition.test_status;
        if (status !== null) {
            stats[status]++;
        }
    }

    return stats;
}

export function getTestStatusFromStats(stats: Readonly<TestStats>): keyof TestStats | null {
    const nb_tests = Object.values(stats).reduce((a: number, b: number): number => {
        return a + b;
    });
    if (nb_tests === 0) {
        return null;
    }

    if (stats.failed > 0) {
        return "failed";
    }

    if (stats.blocked > 0) {
        return "blocked";
    }

    if (stats.notrun > 0) {
        return "notrun";
    }

    return "passed";
}
