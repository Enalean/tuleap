/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import { Card, RootState } from "../../type";
import * as tlp from "tlp";
import * as actions from "./swimlane-actions";
import { RecursiveGetInit } from "tlp";
import { ActionContext } from "vuex";
import { SwimlaneState } from "./type";

jest.mock("tlp");

describe("Swimlane state actions", () => {
    let context: ActionContext<SwimlaneState, RootState>;
    let tlpRecursiveGetMock: jest.SpyInstance;

    beforeEach(() => {
        context = ({
            commit: jest.fn(),
            dispatch: jest.fn(),
            rootState: {
                milestone_id: 42
            } as RootState
        } as unknown) as ActionContext<SwimlaneState, RootState>;
        tlpRecursiveGetMock = jest.spyOn(tlp, "recursiveGet");
    });

    it("Retrieves all top level cards of the taskboard", () => {
        actions.loadSwimlanes(context);
        expect(tlpRecursiveGetMock).toHaveBeenCalledWith(`/api/v1/taskboard/42/cards`, {
            params: {
                limit: 100,
                offset: 0
            },
            getCollectionCallback: expect.any(Function)
        });
    });

    it("Stores the new swimlanes", () => {
        tlpRecursiveGetMock = jest.spyOn(tlp, "recursiveGet").mockImplementation(
            <T>(url: string, init?: RecursiveGetInit<Card[], T>): Promise<Array<T>> => {
                if (!init || !init.getCollectionCallback) {
                    throw new Error();
                }

                return Promise.resolve(
                    init.getCollectionCallback([{ id: 43 } as Card, { id: 44 } as Card])
                );
            }
        );
        actions.loadSwimlanes(context);
        expect(context.commit).toHaveBeenCalledWith("addSwimlanes", [
            { card: { id: 43 } },
            { card: { id: 44 } }
        ]);
    });

    it("Given a rest error, the error message is stored", async () => {
        const error = new Error();
        tlpRecursiveGetMock.mockImplementation(() => {
            throw error;
        });
        await actions.loadSwimlanes(context);
        expect(context.dispatch).toHaveBeenCalledTimes(1);
        expect(context.dispatch).toHaveBeenCalledWith("error/handleErrorMessage", error, {
            root: true
        });
    });
});
