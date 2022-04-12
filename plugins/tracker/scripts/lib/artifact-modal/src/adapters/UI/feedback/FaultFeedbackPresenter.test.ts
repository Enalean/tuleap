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
import { LinkRetrievalFault } from "../../../domain/fields/link-field-v2/LinkRetrievalFault";
import { ParentRetrievalFault } from "../../../domain/parent/ParentRetrievalFault";

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

    it(`translates a message for LinkRetrievalFault`, () => {
        const fault = LinkRetrievalFault(Fault.fromMessage(FAULT_MESSAGE));
        const presenter = FaultFeedbackPresenter.fromFault(fault);
        expect(presenter.message).toContain(FAULT_MESSAGE);
        expect(presenter.message).not.toBe(FAULT_MESSAGE);
    });

    it(`translates a message for ParentRetrievalFault`, () => {
        const fault = ParentRetrievalFault(Fault.fromMessage(FAULT_MESSAGE));
        const presenter = FaultFeedbackPresenter.fromFault(fault);
        expect(presenter.message).toContain(FAULT_MESSAGE);
        expect(presenter.message).not.toBe(FAULT_MESSAGE);
    });
});
