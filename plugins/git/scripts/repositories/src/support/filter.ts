/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
import type { Folder, FormattedGitLabRepository, Repository } from "../type";

function isFolder(item: Folder | Repository | FormattedGitLabRepository): item is Folder {
    return "is_folder" in item;
}

export function createHierarchy(
    hierarchy: Folder | Repository | FormattedGitLabRepository,
    path_part: string
): Folder | Repository | FormattedGitLabRepository {
    if (!isFolder(hierarchy)) {
        throw new Error(path_part + " not found in children");
    }

    if (hierarchy.children instanceof Array) {
        throw new Error("hierarchy is not a map");
    }

    if (!hierarchy.children.has(path_part)) {
        hierarchy.children.set(path_part, {
            is_folder: true,
            label: path_part,
            children: new Map(),
        });
    }

    const child = hierarchy.children.get(path_part);
    if (!child) {
        throw new Error(path_part + " not found in children");
    }
    return child;
}

export function sortByLabelAlphabetically(
    items: Array<Folder | Repository | FormattedGitLabRepository>
): Array<Folder | Repository | FormattedGitLabRepository> {
    return items.sort(
        (
            a: Folder | Repository | FormattedGitLabRepository,
            b: Folder | Repository | FormattedGitLabRepository
        ) => a.label.localeCompare(b.label, undefined, { numeric: true })
    );
}

export function checkRepositoryMatchQuery(
    repository: Folder | Repository | FormattedGitLabRepository,
    query: string
): boolean {
    return (
        repository.normalized_path !== undefined &&
        repository.normalized_path.toLowerCase().includes(query.toLowerCase())
    );
}

export function recursivelySortAlphabetically(
    folder: Folder | Repository | FormattedGitLabRepository
): Folder {
    const folders: Array<Folder> = [];
    const repositories: Array<Folder | Repository | FormattedGitLabRepository> = [];

    if (!isFolder(folder)) {
        throw new Error(`${folder} is not a folder`);
    }

    folder.children.forEach((value: Folder | Repository | FormattedGitLabRepository) => {
        if (isFolder(value)) {
            const sorted_folder = recursivelySortAlphabetically(value);
            folders.push(sorted_folder);
            return;
        }
        repositories.push(value);
    });
    const sorted_children = [
        ...sortByLabelAlphabetically(folders),
        ...sortByLabelAlphabetically(repositories),
    ];

    return {
        ...folder,
        children: sorted_children,
    };
}

export function groupRepositoriesByPath(
    repositories: Array<Folder | Repository | FormattedGitLabRepository>
): Folder {
    const grouped = repositories.reduce(
        (accumulator: Folder, repository: Folder | Repository | FormattedGitLabRepository) => {
            if (repository.path_without_project) {
                const split_path = repository.path_without_project.split("/");
                const end_of_path = split_path.reduce(createHierarchy, accumulator);
                if (isFolder(end_of_path) && end_of_path.children instanceof Map) {
                    end_of_path.children.set(repository.label, repository);
                }
                return accumulator;
            }

            if (isFolder(accumulator) && accumulator.children instanceof Map) {
                accumulator.children.set(repository.label, repository);
            }
            return accumulator;
        },
        {
            is_folder: true,
            label: "root",
            children: new Map(),
        }
    );

    return recursivelySortAlphabetically(grouped);
}

export function filterAFolder(folder: Folder, query: string): Folder {
    if (folder.children instanceof Map) {
        throw new Error("Folder children should be an Array");
    }
    const filtered_children = folder.children.reduce(
        (
            accumulator: Array<Folder | Repository | FormattedGitLabRepository>,
            child: Folder | Repository | FormattedGitLabRepository
        ) => {
            const filtered_child = filterAChild(child, query);
            if (filtered_child) {
                accumulator.push(filtered_child);
            }
            return accumulator;
        },
        []
    );

    return {
        ...folder,
        children: filtered_children,
    };
}

export function filterAChild(
    child: Folder | Repository | FormattedGitLabRepository,
    query: string
): Folder | Repository | FormattedGitLabRepository | null {
    if (isFolder(child)) {
        const filtered_folder = filterAFolder(child, query);
        if (filtered_folder.children instanceof Map) {
            throw new Error("Children should be an Array");
        }
        if (filtered_folder.children.length === 0) {
            return null;
        }
        return filtered_folder;
    }

    if (!checkRepositoryMatchQuery(child, query)) {
        return null;
    }
    return child;
}

export function sortByLastUpdateDate(
    repositories: Array<Folder | Repository | FormattedGitLabRepository>
): Array<Folder | Repository | FormattedGitLabRepository> {
    return repositories.sort(
        (
            a: Folder | Repository | FormattedGitLabRepository,
            b: Folder | Repository | FormattedGitLabRepository
        ) => {
            if (!("last_update_date" in a) || !("last_update_date" in b)) {
                throw new Error("sortByLastUpdateDate is not applied on a correct objet");
            }
            return new Date(b.last_update_date).valueOf() - new Date(a.last_update_date).valueOf();
        }
    );
}
