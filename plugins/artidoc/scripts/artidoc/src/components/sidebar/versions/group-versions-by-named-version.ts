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

import type { Version } from "@/components/sidebar/versions/fake-list-of-versions";

export interface GroupedVersion {
    readonly id: number;
    readonly versions: ReadonlyArray<Version>;
}

export interface Orphans extends GroupedVersion {
    readonly orphan: true;
}

export function isOrphan(group: GroupedVersion): group is Orphans {
    return "orphan" in group;
}

export interface VersionsUnderVersion extends GroupedVersion {
    readonly parent: Version;
}

export function isVersionsUnderVersion(group: GroupedVersion): group is VersionsUnderVersion {
    return "parent" in group;
}

export function groupVersionsByNamedVersion(
    versions: ReadonlyArray<Version>,
): ReadonlyArray<Orphans | VersionsUnderVersion> {
    return versions.reduce((groups: Array<Orphans | VersionsUnderVersion>, version: Version) => {
        if (groups.length === 0) {
            if (version.title.isValue()) {
                groups.push({
                    id: version.id,
                    parent: version,
                    versions: [],
                });
            } else {
                groups.push({
                    id: -1,
                    orphan: true,
                    versions: [version],
                });
            }
            return groups;
        }

        const current = groups[groups.length - 1];
        if (version.title.isValue()) {
            groups.push({
                id: version.id,
                parent: version,
                versions: [],
            });
        } else {
            groups[groups.length - 1] = {
                ...current,
                versions: [...current.versions, version],
            };
        }
        return groups;
    }, []);
}
