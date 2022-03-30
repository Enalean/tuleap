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

import ArrayUtils from "./array-utils";

export { compareArtifacts };

function compareArtifacts(base_artifacts, compared_to_artifacts) {
    return new ArtifactsListComparison(base_artifacts, compared_to_artifacts);
}

/**
 * Compare two list of artifacts by identifying added, removed and modified artifacts.
 */
class ArtifactsListComparison {
    constructor(base_artifacts, compared_to_artifacts) {
        this.base_artifacts = base_artifacts;
        this.compared_to_artifacts = compared_to_artifacts;
    }

    /**
     * returns comparison couples with base and compared to artifacts.
     */
    get identical_or_modified() {
        return this.base_artifacts
            .map((base) => {
                const compared_to = ArrayUtils.find(
                    this.compared_to_artifacts,
                    (compared) => base.id === compared.id
                );
                if (!compared_to) {
                    return null;
                }
                return { base, compared_to };
            })
            .filter((comparison) => comparison !== null);
    }

    /**
     * returns comparison couples with base and compared to artifacts.
     */
    get modified() {
        return this.identical_or_modified.filter(
            ({ base, compared_to }) =>
                base.description !== compared_to.description || base.status !== compared_to.status
        );
    }

    get removed() {
        return this.base_artifacts.filter((base) =>
            this.compared_to_artifacts.every((compared_to) => base.id !== compared_to.id)
        );
    }

    get added() {
        return this.compared_to_artifacts.filter((compared_to) =>
            this.base_artifacts.every((base) => base.id !== compared_to.id)
        );
    }
}
