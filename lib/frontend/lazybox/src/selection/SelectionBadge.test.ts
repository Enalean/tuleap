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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { HostElement } from "./SelectionBadge";
import { getBadgeClasses, onClick } from "./SelectionBadge";

describe("SelectionBadge", () => {
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    describe("Badge classes", () => {
        it.each([[false], [true]])(
            "should take into account the outline attribute when it is %s",
            (outline) => {
                const host = {
                    outline,
                } as HostElement;
                expect(getBadgeClasses(host)["tlp-badge-outline"]).toBe(outline);
            },
        );

        it.each([["primary"], ["fiesta-red"]])(
            "When the color attribute is %s, then it should have a badge class with this color",
            (color) => {
                const host = {
                    color: color,
                } as HostElement;
                expect(Object.keys(getBadgeClasses(host))).toContain(`tlp-badge-${color}`);
            },
        );
    });

    describe(`events`, () => {
        it(`dispatches a "remove-badge" event`, () => {
            const host = doc.createElement("span") as HostElement;
            const dispatchEvent = vi.spyOn(host, "dispatchEvent");
            onClick(host);

            const event = dispatchEvent.mock.calls[0][0];
            expect(event.type).toBe("remove-badge");
        });
    });
});
