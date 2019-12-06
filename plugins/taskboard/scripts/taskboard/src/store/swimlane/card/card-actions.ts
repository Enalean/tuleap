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
import { UpdateCardPayload, NewRemainingEffortPayload, NewCardPayload } from "./type";
import { get, patch, post, put } from "tlp";
import { SwimlaneState } from "../type";
import {
    getPostArtifactBody,
    getPutArtifactBody,
    getPutArtifactBodyToAddChild
} from "../../../helpers/update-artifact";

const headers = {
    "Content-Type": "application/json"
};

export async function saveRemainingEffort(
    context: ActionContext<SwimlaneState, RootState>,
    new_remaining_effort: NewRemainingEffortPayload
): Promise<void> {
    const card = new_remaining_effort.card;
    context.commit("startSavingRemainingEffort", card);
    try {
        await patch(`/api/v1/taskboard_cards/${encodeURIComponent(card.id)}`, {
            headers,
            body: JSON.stringify({ remaining_effort: new_remaining_effort.value })
        });
        context.commit("finishSavingRemainingEffort", new_remaining_effort);
    } catch (error) {
        context.commit("resetSavingRemainingEffort", card);
        await context.dispatch("error/handleModalError", error, { root: true });
    }
}

export async function saveCard(
    context: ActionContext<SwimlaneState, RootState>,
    payload: UpdateCardPayload
): Promise<void> {
    const card = payload.card;
    context.commit("startSavingCard", card);
    try {
        await put(`/api/v1/artifacts/${encodeURIComponent(card.id)}`, {
            headers,
            body: JSON.stringify(getPutArtifactBody(payload))
        });
        context.commit("finishSavingCard", payload);
    } catch (error) {
        context.commit("resetSavingCard", card);
        await context.dispatch("error/handleModalError", error, { root: true });
    }
}

export async function addCard(
    context: ActionContext<SwimlaneState, RootState>,
    payload: NewCardPayload
): Promise<void> {
    try {
        const [new_artifact_response, parent_artifact_response] = await Promise.all([
            post(`/api/v1/artifacts`, {
                headers,
                body: JSON.stringify(getPostArtifactBody(payload, context.rootState.trackers))
            }),
            get(`/api/v1/artifacts/${encodeURIComponent(payload.parent.id)}`)
        ]);
        const [new_artifact, { values }] = await Promise.all([
            new_artifact_response.json(),
            parent_artifact_response.json()
        ]);

        await put(`/api/v1/artifacts/${encodeURIComponent(payload.parent.id)}`, {
            headers,
            body: JSON.stringify(
                getPutArtifactBodyToAddChild(
                    payload,
                    context.rootState.trackers,
                    new_artifact.id,
                    values
                )
            )
        });
    } catch (error) {
        await context.dispatch("error/handleModalError", error, { root: true });
    }
}
