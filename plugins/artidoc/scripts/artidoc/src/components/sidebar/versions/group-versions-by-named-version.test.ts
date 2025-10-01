/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import { groupVersionsByNamedVersion } from "@/components/sidebar/versions/group-versions-by-named-version";
import type { Version } from "@/components/sidebar/versions/fake-list-of-versions";
import type { User } from "@tuleap/core-rest-api-types";
import { Option } from "@tuleap/option";

describe("group-versions-by-named-version", () => {
    const created_on = new Date();
    const created_by: User = {
        id: 102,
        avatar_url: "/path/to/img.png",
        display_name: "Project Member",
        user_url: "/path/to/user",
    };

    function aVersionWithoutTitle(id: number): Version {
        return {
            id,
            title: Option.nothing(),
            description: Option.nothing(),
            created_on,
            created_by,
        };
    }

    function aVersionWithTitle(id: number, title: string): Version {
        return {
            id,
            title: Option.fromValue(title),
            description: Option.nothing(),
            created_on,
            created_by,
        };
    }

    it("Given an empty list of versions, it should produce an empty list of groups", () => {
        expect(groupVersionsByNamedVersion([])).toStrictEqual([]);
    });

    it("Given a list of unnamed versions, it should group them under an orphan group", () => {
        const unnamed_version_1 = aVersionWithoutTitle(1);
        const unnamed_version_2 = aVersionWithoutTitle(2);
        const unnamed_version_3 = aVersionWithoutTitle(3);

        const versions: ReadonlyArray<Version> = [
            unnamed_version_1,
            unnamed_version_2,
            unnamed_version_3,
        ];
        expect(groupVersionsByNamedVersion(versions)).toStrictEqual([
            {
                id: -1,
                orphan: true,
                versions: [unnamed_version_1, unnamed_version_2, unnamed_version_3],
            },
        ]);
    });

    it("Given a list of named versions, it should a group per version", () => {
        const named_version_1 = aVersionWithTitle(1, "a");
        const named_version_2 = aVersionWithTitle(2, "b");
        const named_version_3 = aVersionWithTitle(3, "c");

        const versions: ReadonlyArray<Version> = [
            named_version_1,
            named_version_2,
            named_version_3,
        ];
        expect(groupVersionsByNamedVersion(versions)).toStrictEqual([
            {
                id: 1,
                parent: named_version_1,
                versions: [],
            },
            {
                id: 2,
                parent: named_version_2,
                versions: [],
            },
            {
                id: 3,
                parent: named_version_3,
                versions: [],
            },
        ]);
    });

    it("Given a list of named and unnamed versions, it should a group them per version", () => {
        const unnamed_version_1 = aVersionWithoutTitle(1);
        const unnamed_version_2 = aVersionWithoutTitle(2);
        const unnamed_version_3 = aVersionWithoutTitle(3);
        const unnamed_version_4 = aVersionWithoutTitle(4);
        const unnamed_version_5 = aVersionWithoutTitle(5);
        const named_version_10 = aVersionWithTitle(10, "a");
        const named_version_11 = aVersionWithTitle(11, "b");
        const named_version_12 = aVersionWithTitle(12, "c");

        const versions: ReadonlyArray<Version> = [
            unnamed_version_1,
            unnamed_version_2,
            named_version_10,
            unnamed_version_3,
            named_version_11,
            named_version_12,
            unnamed_version_4,
            unnamed_version_5,
        ];
        expect(groupVersionsByNamedVersion(versions)).toStrictEqual([
            {
                id: -1,
                orphan: true,
                versions: [unnamed_version_1, unnamed_version_2],
            },
            {
                id: 10,
                parent: named_version_10,
                versions: [unnamed_version_3],
            },
            {
                id: 11,
                parent: named_version_11,
                versions: [],
            },
            {
                id: 12,
                parent: named_version_12,
                versions: [unnamed_version_4, unnamed_version_5],
            },
        ]);
    });
});
