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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import * as rest_querier from "../../api/rest-querier";
import { errAsync, okAsync } from "neverthrow";
import type { RestUser } from "../../api/rest-querier";
import { Fault } from "@tuleap/fault";
import { retrieveSelectedOwner } from "./retrieve-selected-owner";

describe("getSelectedOwner", () => {
    it("returns the wanted user", async () => {
        const get_spy = jest.spyOn(rest_querier, "getUserByName");
        get_spy.mockReturnValue(
            okAsync([{ display_name: "John Doe", username: "jdoe" } as RestUser]),
        );

        const user = await retrieveSelectedOwner("jdoe");

        expect(user.username).toBe("jdoe");
        expect(user.display_name).toBe("John Doe");
    });

    it("display the searched username if something went wrong", async () => {
        const get_spy = jest.spyOn(rest_querier, "getUserByName");
        get_spy.mockReturnValue(errAsync(Fault.fromMessage("Something went wrong")));

        const user = await retrieveSelectedOwner("jdoe");

        expect(user.username).toBe("");
        expect(user.display_name).toBe("jdoe");
    });

    it("return an empty user when no user have been retrieved", async () => {
        const get_spy = jest.spyOn(rest_querier, "getUserByName");
        get_spy.mockReturnValue(okAsync([]));

        const user = await retrieveSelectedOwner("jdoe");

        expect(user.username).toBe("");
        expect(user.display_name).toBe("");
    });
});
