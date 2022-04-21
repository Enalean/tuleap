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

import { ParentFeedbackPresenter } from "./ParentFeedbackPresenter";
import type { ParentArtifact } from "../../../domain/parent/ParentArtifact";

describe(`ParentFeedbackPresenter`, () => {
    it(`builds an empty Presenter`, () => {
        const presenter = ParentFeedbackPresenter.buildEmpty();
        expect(presenter.parent_artifact).toBeNull();
    });

    it(`builds a presenter from a parent artifact`, () => {
        const parent_artifact: ParentArtifact = { title: "tractorist" };
        const presenter = ParentFeedbackPresenter.fromArtifact(parent_artifact);
        expect(presenter.parent_artifact).toBe(parent_artifact);
    });
});
