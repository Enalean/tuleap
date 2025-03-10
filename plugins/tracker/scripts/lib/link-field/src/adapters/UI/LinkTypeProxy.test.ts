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

import { describe, expect, it } from "vitest";
import { FORWARD_DIRECTION, UNTYPED_LINK } from "@tuleap/plugin-tracker-constants";
import type { LinkType } from "../../domain/links/LinkType";
import { LinkTypeProxy } from "./LinkTypeProxy";

const LABEL = "Linked to";

describe(`LinkTypeProxy`, () => {
    let type: LinkType;

    const triggerEvent = (): void => {
        const doc = document.implementation.createHTMLDocument();
        const select = doc.createElement("select");
        const selected_option = doc.createElement("option");
        selected_option.selected = true;
        selected_option.label = LABEL;
        selected_option.value = " forward";
        select.append(selected_option);

        select.addEventListener("change", (event): void => {
            const new_type = LinkTypeProxy.fromChangeEvent(event);
            if (!new_type) {
                throw new Error("Expected to build a valid type");
            }
            type = new_type;
        });
        select.dispatchEvent(new Event("change"));
    };

    it(`builds from change event on type select`, () => {
        triggerEvent();
        expect(type.shortname).toBe(UNTYPED_LINK);
        expect(type.direction).toBe(FORWARD_DIRECTION);
        expect(type.label).toBe(LABEL);
    });
});
