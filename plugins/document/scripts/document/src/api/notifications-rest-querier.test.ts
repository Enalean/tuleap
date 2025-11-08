/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { okAsync } from "neverthrow";
import * as fetch_result from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import { getSubscribers } from "./notifications-rest-querier";

describe("notifications-rest-querier", () => {
    describe("getSubscribers() -", () => {
        it("should return users and ugroups monitoring an item", async () => {
            const subscribers = {
                users: [
                    {
                        id: "104",
                        user_url: "/users/userlogin",
                        realname: "User Login",
                        display_name: "User Login (userlogin)",
                        username: "userlogin",
                        avatar_url: "avatar/url.png",
                        is_anonymous: false,
                        has_avatar: true,
                    },
                ],
                user_groups: [
                    {
                        id: "103",
                        label: "dev",
                    },
                ],
            };
            const getJSON = vi.spyOn(fetch_result, "getJSON");
            getJSON.mockReturnValue(okAsync({ subscribers }));

            const result = await getSubscribers(3);

            expect(getJSON).toHaveBeenCalledWith(uri`/plugins/document/3/subscribers`);
            expect(result.isOk()).toBe(true);
        });
    });
});
