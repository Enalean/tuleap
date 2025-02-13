/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import type { Fault } from "@tuleap/fault";
import type { ManageErrorState } from "@/sections/states/SectionErrorManager";
import { noop } from "@/helpers/noop";

export type ManageErrorStateStub = ManageErrorState & {
    getLastHandledFault(): Fault | null;
};

export const SectionErrorManagerStub = {
    withExpectedFault: (): ManageErrorStateStub => {
        let handled_fault: Fault | null = null;

        return {
            getLastHandledFault: (): Fault | null => handled_fault,
            handleError(fault): void {
                handled_fault = fault;
            },
            resetErrorStates: noop,
        };
    },
    withNoExpectedFault: (): ManageErrorState => ({
        handleError(): void {
            throw new Error("Did not expect ManageErrorState::handleError to be called");
        },
        resetErrorStates: noop,
    }),
};
