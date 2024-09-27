/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

import { describe, it, vi, expect } from "vitest";
import type { Command } from "prosemirror-state";
import type { MenuItemSpec } from "prosemirror-menu";
import { MenuItemWithCommandBuilder } from "./BuildMenuItemWithCommand";

describe("MenuItemWithCommandBuilder", () => {
    it("returns run in options when defined", () => {
        const mock_run = vi.fn().mockReturnValue(true);
        const options = {
            run: mock_run,
        } as unknown as MenuItemSpec;
        const cmd = {} as Command;

        const menu_item = MenuItemWithCommandBuilder().buildMenuItemWihCommand(cmd, options);
        expect(menu_item.spec.run).toBe(mock_run);
    });

    it("returns Command passed in parameters when run is not provided in options", () => {
        const options = {} as unknown as MenuItemSpec;
        const cmd = {} as Command;

        const menu_item = MenuItemWithCommandBuilder().buildMenuItemWihCommand(cmd, options);
        expect(menu_item.spec.run).toBe(cmd);
    });

    it.each([
        [true, true, true, true],
        [true, false, true, false],
        [false, true, false, true],
    ])(
        `given select is %s and enable %s then passed options is select %s and enable is %s`,
        (select: boolean, enable: boolean, expected_select: boolean, expected_enable: boolean) => {
            const options = {
                enable,
                select,
            } as unknown as MenuItemSpec;
            const cmd = {} as Command;

            const menu_item = MenuItemWithCommandBuilder().buildMenuItemWihCommand(cmd, options);
            expect(menu_item.spec.select).toBe(expected_select);
            expect(menu_item.spec.enable).toBe(expected_enable);
        },
    );
});
