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

export interface Branch {
    name: string;
    [key: string]: string | number;
}

export interface ExtendedBranch extends Branch {
    display_name: string;
    repository_id: number;
    project_id: number;
}

export function extendBranch(
    branch: Branch,
    repository_id: number,
    project_id: number,
): ExtendedBranch {
    return {
        ...branch,
        display_name: branch.name,
        repository_id,
        project_id,
    };
}

export function extendBranchForParent(
    branch: Branch,
    parent_repository_id: number,
    parent_repository_name: string,
    parent_project_id: number,
): ExtendedBranch {
    return {
        ...branch,
        display_name: `${parent_repository_name} : ${branch.name}`,
        repository_id: parent_repository_id,
        project_id: parent_project_id,
    };
}

export function canCreatePullrequest(
    source_branches: ExtendedBranch[],
    destination_branches: ExtendedBranch[],
): boolean {
    const has_an_unique_branch = source_branches.length === 1 && destination_branches.length === 1;

    return !has_an_unique_branch && source_branches.length > 0 && destination_branches.length > 0;
}

export function getPullrequestUrl(
    pullrequest_id: number,
    repository_id: number,
    project_id: number,
): string {
    return `/plugins/git/?action=pull-requests&tab=overview&repo_id=${repository_id}&group_id=${project_id}#/pull-requests/${pullrequest_id}/overview`;
}
