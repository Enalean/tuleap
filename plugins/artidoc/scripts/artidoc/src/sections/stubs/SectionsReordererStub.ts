/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { ResultAsync } from "neverthrow";
import { errAsync, okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { SectionsReorderer } from "@/sections/reorder/SectionsReorderer";

const result_fault =
    (fault: Fault): (() => ResultAsync<never, Fault>) =>
    () =>
        errAsync(fault);
const result_success = (): ResultAsync<unknown, never> => okAsync(null);

export const SectionsReordererStub = {
    withFault: (fault: Fault): SectionsReorderer => ({
        moveSectionUp: result_fault(fault),
        moveSectionDown: result_fault(fault),
        moveSectionBefore: result_fault(fault),
        moveSectionAtTheEnd: result_fault(fault),
    }),
    withGreatSuccess: (): SectionsReorderer => ({
        moveSectionUp: result_success,
        moveSectionDown: result_success,
        moveSectionBefore: result_success,
        moveSectionAtTheEnd: result_success,
    }),
};
