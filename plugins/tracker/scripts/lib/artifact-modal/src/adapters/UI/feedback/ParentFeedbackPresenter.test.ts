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

import { buildEmpty, buildFromArtifact } from "./ParentFeedbackPresenter";
import type { Artifact } from "../../../domain/Artifact";

describe(`ParentFeedbackPresenter`, () => {
    it(`builds an empty Presenter`, () => {
        const presenter = buildEmpty();
        expect(presenter.parent_artifact).toBeNull();
    });

    it(`builds a presenter from a parent artifact`, () => {
        const parent_artifact = { id: 50 } as Artifact;
        const presenter = buildFromArtifact(parent_artifact);
        expect(presenter.parent_artifact).toBe(parent_artifact);
    });

    it(`builds a presenter without parent artifact`, () => {
        const presenter = buildFromArtifact(null);
        expect(presenter.parent_artifact).toBeNull();
    });
});
