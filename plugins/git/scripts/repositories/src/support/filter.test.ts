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

import {
    createHierarchy,
    filterAChild,
    filterAFolder,
    checkRepositoryMatchQuery,
    groupRepositoriesByPath,
    recursivelySortAlphabetically,
    sortByLabelAlphabetically,
    sortByLastUpdateDate,
} from "./filter";
import type { Folder, Repository } from "../type";

describe("filter", () => {
    describe("createHierarchy", () => {
        it("set children if children is not present in map", () => {
            const hierarchy = {
                is_folder: true,
                label: "tutu",
                children: new Map(),
            } as Folder;
            const path_part = "toto";

            createHierarchy(hierarchy, path_part);

            expect(hierarchy.label).toBe("tutu");
        });

        it("get the children", () => {
            const hierarchy = {
                is_folder: true,
                label: "toto",
                children: new Map(),
            } as Folder;
            const path_part = "toto";

            const result = createHierarchy(hierarchy, path_part);

            expect(hierarchy.label).toEqual(result.label);
        });
    });

    describe("sortByLabelAlphabetically", () => {
        it("sort folders", () => {
            const folderA = {
                is_folder: true,
                label: "Xtoto",
                children: new Map(),
            } as Folder;
            const folderB = {
                is_folder: true,
                label: "Atutu",
                children: new Map(),
            } as Folder;

            const items = [folderA, folderB];

            const sorted_folder = sortByLabelAlphabetically(items);
            expect(sorted_folder).toEqual([folderB, folderA]);
        });
    });

    describe("filterRepositoriesOnName", () => {
        it("returns false if repository path is not defined", () => {
            const repository = {} as Repository;
            const query = "tutu";

            expect(checkRepositoryMatchQuery(repository, query)).toBe(false);
        });
        it("returns false if repository path is defined but do not correspond to search", () => {
            const repository = {
                normalized_path: "toto",
            } as Repository;
            const query = "tutu";

            expect(checkRepositoryMatchQuery(repository, query)).toBe(false);
        });
        it("returns false if repository path is defined and is equal to search", () => {
            const repository = {
                normalized_path: "tutu",
            } as Repository;
            const query = "tutu";

            expect(checkRepositoryMatchQuery(repository, query)).toBe(true);
        });
    });

    describe("recursivelySortAlphabetically", () => {
        it("sort folders", () => {
            const repository_a = {
                id: 29,
                uri: "git/29",
                name: "test",
                label: "test",
                last_update_date: "2020-11-13T16:40:03+01:00",
                normalized_path: "u/user/test",
            } as Repository;
            const repository_b = {
                id: 30,
                uri: "git/30",
                name: "arepository",
                label: "arepository",
                last_update_date: "2020-11-13T16:40:03+01:00",
                normalized_path: "u/user/arepository",
            } as Repository;

            const folder = {
                is_folder: true,
                label: "root",
                children: [
                    {
                        is_folder: true,
                        label: "user",
                        children: [repository_b, repository_a],
                    } as Folder,
                ],
            } as Folder;

            const sorted_folder = {
                is_folder: true,
                label: "root",
                children: [
                    {
                        is_folder: true,
                        label: "user",
                        children: [repository_b, repository_a],
                    } as Folder,
                ],
            } as Folder;

            expect(recursivelySortAlphabetically(folder)).toEqual(sorted_folder);
        });
    });

    describe("groupRepositoriesByPath", () => {
        it("sort folders", () => {
            const repository_a = {
                id: 29,
                uri: "git/29",
                name: "test",
                label: "test",
                last_update_date: "2020-11-13T16:40:03+01:00",
                normalized_path: "u/userA/test",
            } as Repository;
            const repository_b = {
                id: 31,
                uri: "git/31",
                name: "brepository",
                label: "brepository",
                last_update_date: "2020-11-13T16:40:03+01:00",
                normalized_path: "u/userB/arepository",
            } as Repository;
            const repository_c = {
                id: 30,
                uri: "git/30",
                name: "arepository",
                label: "arepository",
                last_update_date: "2020-11-13T16:40:03+01:00",
                normalized_path: "u/userB/arepository",
            } as Repository;

            expect(groupRepositoriesByPath([repository_a, repository_b, repository_c])).toEqual({
                children: [repository_c, repository_b, repository_a],
                is_folder: true,
                label: "root",
            });
        });
    });

    describe("filterAFolder", () => {
        it("filter folder", () => {
            const repository_a = {
                id: 29,
                uri: "git/29",
                name: "test",
                label: "test",
                last_update_date: "2020-11-13T16:40:03+01:00",
                normalized_path: "u/user/test",
            } as Repository;
            const repository_b = {
                id: 30,
                uri: "git/30",
                name: "arepository",
                label: "arepository",
                last_update_date: "2020-11-13T16:40:03+01:00",
                normalized_path: "u/user/arepository",
            } as Repository;

            const folder = {
                is_folder: true,
                label: "root",
                children: [repository_a, repository_b],
            } as Folder;

            const filtered_folder = {
                is_folder: true,
                label: "root",
                children: [repository_a],
            } as Folder;

            expect(filterAFolder(folder, "test")).toEqual(filtered_folder);
        });
    });

    describe("filterAChild", () => {
        it("filter a folder", () => {
            const repository_a = {
                id: 29,
                name: "test",
                normalized_path: "u/username/test",
            } as Repository;

            const folder = {
                is_folder: true,
                label: "root",
                children: [
                    {
                        is_folder: true,
                        label: "username",
                        children: [repository_a],
                    } as Folder,
                ],
            } as Folder;

            expect(filterAChild(folder, "username")).toEqual(folder);
        });

        it("filter a repository", () => {
            const repository_a = {
                id: 29,
                name: "test",
                normalized_path: "u/username/test",
            } as Repository;

            const repository_b = {
                id: 29,
                name: "other",
                normalized_path: "u/username/other",
            } as Repository;

            const folder = {
                is_folder: true,
                label: "root",
                children: [repository_a, repository_b],
            } as Folder;

            const expected = {
                is_folder: true,
                label: "root",
                children: [repository_a],
            } as Folder;

            expect(filterAChild(folder, "test")).toEqual(expected);
        });
    });

    describe("sortByLastUpdateDate", () => {
        it("displays most recently updated repository first", () => {
            const repository_a = {
                id: 29,
                label: "test",
                last_update_date: "2020-11-10T16:40:03+01:00",
            } as Repository;
            const repository_b = {
                id: 30,
                label: "arepository",
                last_update_date: "2020-11-13T12:24:03+01:00",
            } as Repository;

            expect(sortByLastUpdateDate([repository_a, repository_b])).toEqual([
                repository_b,
                repository_a,
            ]);
        });
    });
});
