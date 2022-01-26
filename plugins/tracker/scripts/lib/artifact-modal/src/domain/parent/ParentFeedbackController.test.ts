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

import { ParentFeedbackController } from "./ParentFeedbackController";
import { StubWithParent } from "../../../tests/stubs/RetrieveParentStub";
import type { ParentFeedbackPresenter } from "./ParentFeedbackPresenter";

const PARENT_ARTIFACT_ID = 78;

describe(`ParentFeedbackController`, () => {
    let parent_artifact_id: number | null;
    beforeEach(() => {
        parent_artifact_id = PARENT_ARTIFACT_ID;
    });

    const displayParentFeedback = (): Promise<ParentFeedbackPresenter> => {
        const parent_artifact = { id: PARENT_ARTIFACT_ID, title: "nonhereditary" };
        const controller = ParentFeedbackController(
            StubWithParent(parent_artifact),
            parent_artifact_id
        );
        return controller.displayParentFeedback();
    };

    describe(`displayParentFeedback()`, () => {
        it(`when there is a parent, it will return a presenter with it`, async () => {
            const presenter = await displayParentFeedback();

            if (presenter.parent_artifact === null) {
                throw new Error("Expected a parent artifact");
            }
            expect(presenter.parent_artifact.id).toBe(PARENT_ARTIFACT_ID);
        });

        it(`when there is no parent artifact, it will return a presenter without parent`, async () => {
            parent_artifact_id = null;
            const presenter = await displayParentFeedback();
            expect(presenter.parent_artifact).toBeNull();
        });
    });
});
