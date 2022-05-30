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

import { Fault } from "@tuleap/fault";
import { FaultFeedbackPresenter } from "./FaultFeedbackPresenter";
import { setCatalog } from "../../../gettext-catalog";
import { LinkRetrievalFault } from "../../../domain/fields/link-field/LinkRetrievalFault";
import { ParentRetrievalFault } from "../../../domain/parent/ParentRetrievalFault";
import { MatchingArtifactRetrievalFault } from "../../../domain/fields/link-field/MatchingArtifactRetrievalFault";
import { PossibleParentsRetrievalFault } from "../../../domain/fields/link-field/PossibleParentsRetrievalFault";

const FAULT_MESSAGE = "An error occurred";

describe(`FaultFeedbackPresenter`, () => {
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
    });

    it(`builds an empty presenter`, () => {
        const presenter = FaultFeedbackPresenter.buildEmpty();
        expect(presenter.message).toBe("");
    });

    it(`casts a Fault to string`, () => {
        const fault = Fault.fromMessage(FAULT_MESSAGE);
        const presenter = FaultFeedbackPresenter.fromFault(fault);
        expect(presenter.message).toBe(String(fault));
    });

    it.each([
        ["LinkRetrievalFault", LinkRetrievalFault(Fault.fromMessage(FAULT_MESSAGE))],
        ["ParentRetrievalFault", ParentRetrievalFault(Fault.fromMessage(FAULT_MESSAGE))],
        [
            "MatchingArtifactRetrievalFault",
            MatchingArtifactRetrievalFault(Fault.fromMessage(FAULT_MESSAGE)),
        ],
        [
            "PossibleParentsRetrievalFault",
            PossibleParentsRetrievalFault(Fault.fromMessage(FAULT_MESSAGE)),
        ],
    ])(`translates a message for %s`, (fault_name, fault) => {
        const presenter = FaultFeedbackPresenter.fromFault(fault);
        expect(presenter.message).toContain(FAULT_MESSAGE);
        expect(presenter.message).not.toBe(FAULT_MESSAGE);
    });
});
