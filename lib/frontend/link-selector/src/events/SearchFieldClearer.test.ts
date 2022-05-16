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

import { SearchFieldClearer } from "./SearchFieldClearer";

describe("SearchFieldClearer", () => {
    it('should clear the field and trigger an "input" event', () => {
        const search_field = document.implementation.createHTMLDocument().createElement("input");
        search_field.value = "An query about to be cleared";
        const dispatchEvent = jest.spyOn(search_field, "dispatchEvent");

        SearchFieldClearer(search_field).clearSearchField();

        expect(search_field.value).toBe("");
        expect(dispatchEvent).toHaveBeenCalledTimes(1);

        const type_of_event_dispatched = dispatchEvent.mock.calls[0][0].type;
        expect(type_of_event_dispatched).toBe("input");
    });
});
