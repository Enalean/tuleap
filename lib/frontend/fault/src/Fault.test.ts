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

import { describe, it, expect } from "vitest";
import { Fault, isFault } from "./Fault";

const FAULT_MESSAGE = "User is not allowed to do that";
const ERROR_MESSAGE = "An Error was thrown";

const getErrorInTest = (): Error => new Error(ERROR_MESSAGE);

interface SpecializedFault extends Fault {
    isPermissionDenied: () => true;
}

const isSpecializedFault = (fault: Fault): fault is SpecializedFault =>
    "isPermissionDenied" in fault && fault.isPermissionDenied() === true;

const getUnknownFault = (): unknown => Fault.fromMessage(FAULT_MESSAGE);

describe(`Fault`, () => {
    it(`builds a new Fault with a message`, function expectedFunctionName() {
        const fault = Fault.fromMessage(FAULT_MESSAGE);
        expect(isFault(fault)).toBe(true);
        expect(String(fault)).toBe(FAULT_MESSAGE);
        expect(fault.getStackTraceAsString()).toContain("expectedFunctionName");
    });

    it(`builds a new Fault from an Error`, () => {
        const fault = Fault.fromError(getErrorInTest());
        expect(isFault(fault)).toBe(true);
        expect(String(fault)).toBe(ERROR_MESSAGE);
        expect(fault.getStackTraceAsString()).toContain("getErrorInTest");
    });

    it(`builds a new Fault from an Error and a message`, () => {
        const fault = Fault.fromErrorWithMessage(getErrorInTest(), FAULT_MESSAGE);
        expect(isFault(fault)).toBe(true);
        expect(String(fault)).toBe(FAULT_MESSAGE);
        expect(fault.getStackTraceAsString()).toContain("getErrorInTest");
    });

    it(`can recognize a Fault`, () => {
        const fault = getUnknownFault();
        expect(isFault(fault)).toBe(true);
        if (!isFault(fault)) {
            throw new Error("Expected Fault to be recognized");
        }
        expect(fault.getStackTraceAsString()).toBeDefined();
    });

    it(`can be converted to a primitive string`, () => {
        const fault = Fault.fromMessage(FAULT_MESSAGE);
        expect(Number.isNaN(Number(fault))).toBe(true);
    });

    it(`can be extended`, function expectedFunctionName() {
        const PermissionFault = (): SpecializedFault => {
            const fault = Fault.fromMessage(FAULT_MESSAGE);
            return {
                isPermissionDenied: () => true,
                ...fault,
            };
        };

        const specialized_fault: Fault = PermissionFault();
        expect(isFault(specialized_fault)).toBe(true);
        if (!isSpecializedFault(specialized_fault)) {
            throw new Error(
                `Expected specialized fault to have a method named "isPermissionDenied"`,
            );
        }
        expect(specialized_fault.isPermissionDenied()).toBe(true);
        expect(String(specialized_fault)).toBe(FAULT_MESSAGE);
        expect(specialized_fault.getStackTraceAsString()).toContain("expectedFunctionName");
    });
});
