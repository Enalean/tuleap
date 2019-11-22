/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { ActionContext } from "vuex";
import { RootState } from "../../type";
import { NewRemainingEffortPayload } from "./type";
import * as tlp from "tlp";
import { SwimlaneState } from "../type";
import { Card } from "../../../type";
import * as actions from "./card-actions";
import {
    mockFetchError,
    mockFetchSuccess
} from "../../../../../../../../src/www/themes/common/tlp/mocks/tlp-fetch-mock-helper";

jest.mock("tlp");

describe("Card actions", () => {
    let context: ActionContext<SwimlaneState, RootState>;

    beforeEach(() => {
        jest.clearAllMocks();
        context = ({
            commit: jest.fn(),
            dispatch: jest.fn(),
            rootState: {
                milestone_id: 42,
                user: {
                    user_id: 101
                }
            } as RootState
        } as unknown) as ActionContext<SwimlaneState, RootState>;
    });

    describe("saveRemainingEffort", () => {
        it("saves the new value", async () => {
            const card: Card = { id: 123 } as Card;
            const new_remaining_effort: NewRemainingEffortPayload = {
                card,
                value: 42
            };

            const tlpPatchMock = jest.spyOn(tlp, "patch");
            mockFetchSuccess(tlpPatchMock, {});

            await actions.saveRemainingEffort(context, new_remaining_effort);
            expect(context.commit).toHaveBeenCalledWith("startSavingRemainingEffort", card);
            expect(tlpPatchMock).toHaveBeenCalledWith(`/api/v1/taskboard_cards/123`, {
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    remaining_effort: 42
                })
            });
            expect(context.commit).toHaveBeenCalledWith(
                "finishSavingRemainingEffort",
                new_remaining_effort
            );
        });

        it("warns about error if any", async () => {
            const card: Card = { id: 123 } as Card;
            const new_remaining_effort: NewRemainingEffortPayload = {
                card,
                value: 42
            };

            const tlpPatchMock = jest.spyOn(tlp, "patch");
            mockFetchError(tlpPatchMock, {});

            await actions.saveRemainingEffort(context, new_remaining_effort);

            expect(context.commit).not.toHaveBeenCalledWith(
                "finishSavingRemainingEffort",
                new_remaining_effort
            );
            expect(context.commit).toHaveBeenCalledWith("resetSavingRemainingEffort", card);
            expect(context.dispatch).toHaveBeenCalledWith(
                "error/handleModalError",
                expect.anything(),
                { root: true }
            );
        });
    });
});
