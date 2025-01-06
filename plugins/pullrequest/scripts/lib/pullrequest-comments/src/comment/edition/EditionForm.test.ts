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

import { describe, it, expect, vi } from "vitest";
import { ControlEditionFormStub } from "../../../tests/stubs/ControlEditionFormStub";
import type { InternalEditionForm } from "./EditionForm";
import { after_render_once_descriptor } from "./EditionForm";

vi.mock("@tuleap/mention", () => ({
    initMentions(): void {
        // Mock @tuleap/mention because it needs jquery in tests
    },
}));

describe("EditionForm", () => {
    it("When the EditionForm has been rendered once, Then its controller should initialize it", () => {
        const controller = ControlEditionFormStub();
        const initEditionForm = vi.spyOn(controller, "initEditionForm");
        const host = {
            controller,
        } as unknown as InternalEditionForm;

        after_render_once_descriptor.observe(host);

        expect(initEditionForm).toHaveBeenCalledOnce();
    });
});
