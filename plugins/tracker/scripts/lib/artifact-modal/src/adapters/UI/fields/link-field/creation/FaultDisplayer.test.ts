/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import { setCatalog } from "../../../../../gettext-catalog";
import { Fault } from "@tuleap/fault";
import { FaultDisplayer } from "./FaultDisplayer";
import { ProjectsRetrievalFault } from "../../../../../domain/fields/link-field/creation/ProjectsRetrievalFault";

const FAULT_MESSAGE = "An error occurred";

describe(`FaultDisplayer`, () => {
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
    });

    const format = (fault: Fault): string => {
        return FaultDisplayer().formatForDisplay(fault);
    };

    it(`casts a Fault to string`, () => {
        const fault = Fault.fromMessage(FAULT_MESSAGE);
        expect(format(fault)).toBe(String(fault));
    });

    function* generateSpecialFaults(): Generator<[string, Fault]> {
        yield ["ProjectsRetrievalFault", ProjectsRetrievalFault(Fault.fromMessage(FAULT_MESSAGE))];
    }

    it.each([...generateSpecialFaults()])(
        `Adds a translated message for %s`,
        (fault_name, fault) => {
            const message = format(fault);
            expect(message).toContain(FAULT_MESSAGE);
            expect(message).not.toBe(FAULT_MESSAGE);
        }
    );
});
