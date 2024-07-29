/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { Fault } from "@tuleap/fault";
import { useFeedbacks } from "./useFeedbacks";

describe(`useFeedbacks`, () => {
    it(`stores and clears a Fault`, () => {
        const { current_fault, notifyFault, clearFeedbacks } = useFeedbacks();
        expect(current_fault.value.isNothing()).toBe(true);

        const fault = Fault.fromMessage("An error occurred");
        notifyFault(fault);
        expect(current_fault.value.unwrapOr(null)).toBe(fault);

        clearFeedbacks();
        expect(current_fault.value.isNothing()).toBe(true);
    });

    it(`stores and clears a success message`, () => {
        const { current_success, notifySuccess, clearFeedbacks } = useFeedbacks();
        expect(current_success.value.isNothing()).toBe(true);

        const success_message = "Great success";
        notifySuccess(success_message);
        expect(current_success.value.unwrapOr("")).toBe(success_message);

        clearFeedbacks();
        expect(current_success.value.isNothing()).toBe(true);
    });
});
