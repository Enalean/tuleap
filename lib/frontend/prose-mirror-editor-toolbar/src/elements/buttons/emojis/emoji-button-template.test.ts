/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it } from "vitest";
import { createLocalDocument, gettext_provider } from "../../../helpers/helper-for-test";
import type { HostElement } from "./emojis";
import { renderEmojiButton } from "./emoji-button-template";

describe("emoji-button-template", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        const doc = createLocalDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
    });

    it.each([
        [false, true, "it should NOT have the button-active class"],
        [true, false, "it should have the button-active class"],
    ])(
        "When is_activated is %s and is_disabled is %s, then %s",
        (is_activated: boolean, is_disabled: boolean) => {
            const host = { is_activated, is_disabled } as HostElement;

            renderEmojiButton(host, gettext_provider)(host, target);

            const button = target.querySelector<HTMLButtonElement>("[data-test=button-emoji]");
            if (!button) {
                throw new Error("Expected a button");
            }

            expect(button.classList.contains("button-active")).toBe(is_activated);
        },
    );

    it.each([
        [false, "it should not have the disabled attribute"],
        [true, "it should have the disabled attribute"],
    ])("When is_disabled is %s, then %s", (is_disabled) => {
        const host = { is_disabled } as HostElement;

        renderEmojiButton(host, gettext_provider)(host, target);

        const button = target.querySelector<HTMLButtonElement>("[data-test=button-emoji]");
        if (!button) {
            throw new Error("Expected a button");
        }

        expect(button.hasAttribute("disabled")).toBe(is_disabled);
    });
});
