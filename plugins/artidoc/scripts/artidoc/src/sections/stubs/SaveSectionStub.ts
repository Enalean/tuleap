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

import type { SaveSection } from "@/sections/save/SectionSaver";

export type SaveSectionStub = SaveSection & {
    hasBeenForceSaved(): boolean;
    hasBeenNormallySaved(): boolean;
};

const throwUnexpectedCallError = (method_name: string): void => {
    throw new Error(`Did not expect SaveSection::${method_name} to be called.`);
};

export const SaveSectionStub = {
    withExpectForceSave(): SaveSectionStub {
        let has_been_force_saved = false;

        return {
            hasBeenForceSaved: () => has_been_force_saved,
            hasBeenNormallySaved: () => false,
            forceSave(): void {
                has_been_force_saved = true;
            },
            save(): void {
                throwUnexpectedCallError("save");
            },
        };
    },
    withExpectNormalSave(): SaveSectionStub {
        let has_been_normally_saved = false;

        return {
            hasBeenForceSaved: () => false,
            hasBeenNormallySaved: () => has_been_normally_saved,
            forceSave(): void {
                throwUnexpectedCallError("forceSave");
            },
            save(): void {
                has_been_normally_saved = true;
            },
        };
    },
    withNoExpectedCall: (): SaveSection => ({
        forceSave: (): void => throwUnexpectedCallError("forceSave"),
        save: (): void => throwUnexpectedCallError("save"),
    }),
};
