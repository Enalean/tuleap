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
import { selectOrThrow } from "@tuleap/dom";
import { GettextProviderStub } from "../../../tests/stubs/GettextProviderStub";
import type { HostElement } from "./EditionForm";
import { getEditionForm } from "./EditionFormTemplate";

describe("EditionFormTemplate", () => {
    it("should dispatch a cancel-edition event when the user clicks [Cancel]", () => {
        const doc = document.implementation.createHTMLDocument();
        const host = doc.createElement("div") as unknown as HostElement;
        const target = doc.createElement("div") as unknown as ShadowRoot;
        const render = getEditionForm(host, GettextProviderStub);
        const dispatchEvent = vi.spyOn(host, "dispatchEvent");

        render(host, target);

        selectOrThrow(target, "[data-test=button-cancel-edition]").click();

        expect(dispatchEvent).toHaveBeenCalledOnce();
        expect(dispatchEvent.mock.calls[0][0].type).toBe("cancel-edition");
    });
});
