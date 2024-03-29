/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
import type { BuildGroupOfLabels } from "./GroupOfLabelsBuilder";
import { GroupOfLabelsBuilder } from "./GroupOfLabelsBuilder";

const getGroupBuilder = (): BuildGroupOfLabels =>
    GroupOfLabelsBuilder((msgid: string): string => msgid);

describe("GroupOfLabelsBuilder", () => {
    it("buildWithLabels() should build a group containing the provided labels", () => {
        const group = getGroupBuilder().buildWithLabels([
            {
                value: {
                    label: "Easy fix",
                    color: "peggy-pink",
                    is_outline: true,
                },
                is_disabled: false,
            },
            {
                value: {
                    label: "Emergency",
                    color: "fiesta-red",
                    is_outline: false,
                },
                is_disabled: false,
            },
        ]);
        expect(group.is_loading).toBe(false);
        expect(group.empty_message).toBe("No matching labels found");
        expect(group.items).toHaveLength(2);
    });
});
