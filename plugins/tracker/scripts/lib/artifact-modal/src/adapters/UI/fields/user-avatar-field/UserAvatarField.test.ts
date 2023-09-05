/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { UserAvatarField } from "./UserAvatarField";
import type { HostElement } from "./UserAvatarField";

const field_label = "Submitted by",
    user_name = "Bob Le moche",
    user_url = "/url/to/boblm";

function getHost(avatar_url: string): HostElement {
    return {
        field: {
            label: field_label,
            value: {
                avatar_url,
                user_url: user_url,
                display_name: user_name,
            },
        },
    } as unknown as HostElement;
}

describe("UserAvatarField", () => {
    it.each([
        ["/url/to/boblm/avatar", "will be", true],
        ["", "won't", false],
    ])(
        'When the avatar url is "%s", then the avatar %s be displayed',
        (avatar_url: string, expectation: string, will_avatar_be_displayed: boolean) => {
            const target = document.implementation
                .createHTMLDocument()
                .createElement("div") as unknown as ShadowRoot;

            const host = getHost(avatar_url);

            const update = UserAvatarField.content(host);

            update(host, target);

            const label = target.querySelector("[data-test=user-avatar-field-label]");
            const avatar = target.querySelector("[data-test=user-avatar-field-avatar-image]");
            const link = target.querySelector("[data-test=user-avatar-field-user-link]");

            if (!(label instanceof HTMLElement) || !(link instanceof HTMLAnchorElement)) {
                throw new Error("An element is missing in UserAvatarField");
            }

            expect(Boolean(avatar)).toBe(will_avatar_be_displayed);

            expect(label.textContent?.trim()).toBe(field_label);
            expect(link.href).toBe(user_url);
            expect(link.textContent?.trim()).toBe(user_name);
        },
    );
});
