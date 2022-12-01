/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

type PullRequestDiffMode = "side_by_side" | "unified";

export const SIDE_BY_SIDE_DIFF: PullRequestDiffMode = "side_by_side";
export const UNIFIED_DIFF: PullRequestDiffMode = "unified";

let current_diff_mode: PullRequestDiffMode = UNIFIED_DIFF;

export function setMode(current_mode: PullRequestDiffMode): void {
    current_diff_mode = current_mode;
}

export function isUnifiedMode(): boolean {
    return current_diff_mode === UNIFIED_DIFF;
}

export function isSideBySideMode(): boolean {
    return current_diff_mode === SIDE_BY_SIDE_DIFF;
}
