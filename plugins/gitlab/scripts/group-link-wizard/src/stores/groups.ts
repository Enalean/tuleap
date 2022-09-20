/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { defineStore } from "pinia";
import type { GroupsState, GitlabGroup } from "./types";

export const useGitLabGroupsStore = defineStore("groups", {
    state: (): GroupsState => ({
        groups: [],
        selected_group: null,
    }),
    actions: {
        setGroups(groups: readonly GitlabGroup[]): void {
            this.groups = groups;
        },
        setSelectedGroup(group: GitlabGroup): void {
            this.selected_group = group;
        },
    },
});
