/*
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

import type { UserStory, Iteration, State } from "../type";

export const getIterationContentFromStore =
    (state: State) =>
    (iteration: Iteration): UserStory[] => {
        const user_stories = state.iterations_content.get(iteration.id);
        if (!user_stories) {
            return [];
        }

        return user_stories;
    };

export const hasIterationContentInStore =
    (state: State) =>
    (iteration: Iteration): boolean => {
        return state.iterations_content.has(iteration.id);
    };
