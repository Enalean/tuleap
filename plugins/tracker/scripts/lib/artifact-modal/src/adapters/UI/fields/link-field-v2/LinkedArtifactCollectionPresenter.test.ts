/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { LinkedArtifactCollectionPresenter } from "./LinkedArtifactCollectionPresenter";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import { LinkedArtifactStub } from "../../../../../tests/stubs/LinkedArtifactStub";

describe(`LinkedArtifactCollectionPresenter`, () => {
    it(`builds a loading state`, () => {
        const presenter = LinkedArtifactCollectionPresenter.buildLoadingState();
        expect(presenter.linked_artifacts).toHaveLength(0);
        expect(presenter.is_loading).toBe(true);
        expect(presenter.has_loaded_content).toBe(false);
    });

    it(`builds from linked artifacts`, () => {
        const first_artifact = LinkedArtifactPresenter.fromLinkedArtifact(
            LinkedArtifactStub.withDefaults({ title: "bribery" }),
            false
        );
        const second_artifact = LinkedArtifactPresenter.fromLinkedArtifact(
            LinkedArtifactStub.withDefaults({ title: "versicolorate" }),
            false
        );

        const presenter = LinkedArtifactCollectionPresenter.fromArtifacts([
            first_artifact,
            second_artifact,
        ]);
        expect(presenter.linked_artifacts).toContain(first_artifact);
        expect(presenter.linked_artifacts).toContain(second_artifact);
        expect(presenter.is_loading).toBe(false);
        expect(presenter.has_loaded_content).toBe(true);
    });

    it(`builds for fault`, () => {
        const presenter = LinkedArtifactCollectionPresenter.forFault();
        expect(presenter.linked_artifacts).toHaveLength(0);
        expect(presenter.is_loading).toBe(false);
        expect(presenter.has_loaded_content).toBe(true);
    });
});
