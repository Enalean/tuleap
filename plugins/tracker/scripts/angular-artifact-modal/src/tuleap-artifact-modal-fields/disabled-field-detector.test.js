/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { isDisabled } from "./disabled-field-detector.js";
import * as modal_creation_mode_state from "../modal-creation-mode-state.js";

describe(`disabled-field-detector`, () => {
    describe(`isDisabled()`, () => {
        describe(`given the modal is in Creation mode`, () => {
            beforeEach(() => {
                jest.spyOn(modal_creation_mode_state, "isInCreationMode").mockReturnValue(true);
            });

            it(`when the field has the "create" permission, then it returns false`, () => {
                const field = { permissions: ["read", "create"] };
                expect(isDisabled(field)).toBe(false);
            });

            it(`when the field does not have the "create" permission, then it returns true`, () => {
                const field = { permissions: ["read", "update"] };
                expect(isDisabled(field)).toBe(true);
            });
        });

        describe(`given the modal is in Edition mode`, () => {
            beforeEach(() => {
                jest.spyOn(modal_creation_mode_state, "isInCreationMode").mockReturnValue(false);
            });

            it(`when the field has the "update" permission, then it returns false`, () => {
                const field = { permissions: ["read", "update"] };
                expect(isDisabled(field)).toBe(false);
            });

            it(`when the field does not have the "update" permission, then it returns true`, () => {
                const field = { permissions: ["read", "create"] };
                expect(isDisabled(field)).toBe(true);
            });
        });
    });
});
