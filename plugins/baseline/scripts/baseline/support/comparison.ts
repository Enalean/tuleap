/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

import type { Artifact } from "../type";

export function compareArtifacts(
    base_artifacts: ReadonlyArray<Artifact>,
    compared_to_artifacts: ReadonlyArray<Artifact>,
): ArtifactsListComparison {
    return new ArtifactsListComparison(base_artifacts, compared_to_artifacts);
}

interface Couple {
    readonly base: Artifact;
    readonly compared_to: Artifact;
}

/**
 * Compare two list of artifacts by identifying added, removed and modified artifacts.
 */
export class ArtifactsListComparison {
    private base_artifacts: ReadonlyArray<Artifact>;
    private compared_to_artifacts: ReadonlyArray<Artifact>;

    constructor(
        base_artifacts: ReadonlyArray<Artifact>,
        compared_to_artifacts: ReadonlyArray<Artifact>,
    ) {
        this.base_artifacts = base_artifacts;
        this.compared_to_artifacts = compared_to_artifacts;
    }

    /**
     * returns comparison couples with base and compared to artifacts.
     */
    get identical_or_modified(): Array<Couple> {
        return this.base_artifacts.reduce(
            (accumulator: Array<Couple>, base: Artifact): Array<Couple> => {
                const compared_to = this.compared_to_artifacts.find(
                    (compared) => base.id === compared.id,
                );
                if (compared_to) {
                    accumulator.push({ base, compared_to });
                }
                return accumulator;
            },
            [],
        );
    }

    /**
     * returns comparison couples with base and compared to artifacts.
     */
    get modified(): Array<Couple> {
        return this.identical_or_modified.filter(
            ({ base, compared_to }) =>
                base.description !== compared_to.description || base.status !== compared_to.status,
        );
    }

    get removed(): Array<Artifact> {
        return this.base_artifacts.filter((base) =>
            this.compared_to_artifacts.every((compared_to) => base.id !== compared_to.id),
        );
    }

    get added(): Array<Artifact> {
        return this.compared_to_artifacts.filter((compared_to) =>
            this.base_artifacts.every((base) => base.id !== compared_to.id),
        );
    }
}
