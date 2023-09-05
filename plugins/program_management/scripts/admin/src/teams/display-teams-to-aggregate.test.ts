/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import { displayTeamsToAggregate } from "./display-teams-to-aggregate";
import type { GetText } from "@tuleap/vue2-gettext-init";
import * as listPicker from "@tuleap/list-picker";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe("DisplayTeamsToAggregate", () => {
    describe("displayTeamsToAggregate", () => {
        const gettext: GetText = {
            gettext: (msgid: string) => {
                return msgid;
            },
        } as GetText;

        it("Given document without list, Then error is thrown", () => {
            const doc = createDocument();

            expect(() => displayTeamsToAggregate(gettext, doc)).toThrowError(
                "No list to pick teams",
            );
        });

        it("Given document with list, Then list picker is created", () => {
            const doc = createDocument();
            doc.body.setAttribute("data-user-locale", "en-EN");

            const select = document.createElement("select");
            select.id = "program-management-choose-teams";
            doc.body.appendChild(select);

            const create_list_picker = jest.spyOn(listPicker, "createListPicker").mockReturnValue({
                destroy: () => {
                    // Nothing to do since we did not really create something
                },
            });

            displayTeamsToAggregate(gettext, doc);
            expect(create_list_picker).toHaveBeenCalledWith(select, {
                is_filterable: true,
                locale: "en-EN",
                placeholder: "Choose a project to aggregate",
            });
        });
    });
});
