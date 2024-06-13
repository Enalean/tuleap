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
 */

import { describe, expect, it } from "vitest";
import { useOpenConfigurationModalBus } from "@/composables/useOpenConfigurationModalBus";

describe("useOpenConfigurationModalBus", () => {
    it("should call the open modal handler", () => {
        let has_been_called = false;

        const bus = useOpenConfigurationModalBus();
        bus.registerHandler(() => {
            has_been_called = true;
        });

        expect(has_been_called).toBe(false);

        bus.openModal();

        expect(has_been_called).toBe(true);
    });
});
