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
import type { ActionContext } from "vuex";
import type { RootState } from "../../type";
import type {
    NewCardPayload,
    NewRemainingEffortPayload,
    UpdateCardPayload,
    UserForPeoplePicker,
} from "./type";
import { get, patch, post, put } from "@tuleap/tlp-fetch";
import type { RefreshCardMutationPayload, SwimlaneState } from "../type";
import {
    getPostArtifactBody,
    getPutArtifactBody,
    getPutArtifactBodyToAddChild,
} from "../../../helpers/update-artifact";
import { injectDefaultPropertiesInCard } from "../../../helpers/card-default";
import type { Card, Swimlane, Tracker, User } from "../../../type";
import pRetry from "p-retry";

const headers = {
    "Content-Type": "application/json",
};

const retry_options = {
    minTimeout: 100,
    maxTimeout: 10000,
    randomize: true,
};

export async function saveRemainingEffort(
    context: ActionContext<SwimlaneState, RootState>,
    new_remaining_effort: NewRemainingEffortPayload,
): Promise<void> {
    const card = new_remaining_effort.card;
    context.commit("startSavingRemainingEffort", new_remaining_effort);
    try {
        await patch(`/api/v1/taskboard_cards/${encodeURIComponent(card.id)}`, {
            headers,
            body: JSON.stringify({ remaining_effort: new_remaining_effort.value }),
        });
        context.commit("resetSavingRemainingEffort", card);
    } catch (error) {
        context.commit("resetSavingRemainingEffort", card);
        await context.dispatch("error/handleModalError", error, { root: true });
    }
}

export async function saveCard(
    context: ActionContext<SwimlaneState, RootState>,
    payload: UpdateCardPayload,
): Promise<void> {
    const card = payload.card;
    context.commit("startSavingCard", card);
    try {
        await put(`/api/v1/artifacts/${encodeURIComponent(card.id)}`, {
            headers,
            body: JSON.stringify(getPutArtifactBody(payload)),
        });
        context.commit("finishSavingCard", payload);
        const refreshed_card_response = await get(`/api/v1/taskboard_cards/${card.id}`, {
            params: { milestone_id: context.rootState.milestone_id },
        });
        const refreshed_card = await refreshed_card_response.json();
        const refresh_payload: RefreshCardMutationPayload = { refreshed_card };
        context.commit("refreshCard", refresh_payload);
    } catch (error) {
        context.commit("resetSavingCard", card);
        await context.dispatch("error/handleModalError", error, { root: true });
    }
}

export async function addCard(
    context: ActionContext<SwimlaneState, RootState>,
    payload: NewCardPayload,
): Promise<void> {
    context.commit("startCreatingCard");
    try {
        const new_artifact_response = await post(`/api/v1/artifacts`, {
            headers,
            body: JSON.stringify(getPostArtifactBody(payload, context.rootState.trackers)),
        });
        const new_artifact = await new_artifact_response.json();

        const [new_card] = await Promise.all([
            injectNewCardInStore(context, new_artifact.id, payload.swimlane),
            linkCardToItsParent(context, new_artifact.id, payload),
        ]);
        context.commit("finishCreatingCard", new_card);
    } catch (error) {
        await context.dispatch("error/handleModalError", error, { root: true });
    }
}

async function injectNewCardInStore(
    context: ActionContext<SwimlaneState, RootState>,
    new_artifact_id: number,
    swimlane: Swimlane,
): Promise<Card> {
    const card_response = await get(
        `/api/v1/taskboard_cards/${encodeURIComponent(
            new_artifact_id,
        )}?milestone_id=${encodeURIComponent(context.rootState.milestone_id)}`,
    );
    const card: Card = await card_response.json();
    injectDefaultPropertiesInCard(card);
    card.is_being_saved = true;
    context.commit("addChildrenToSwimlane", {
        swimlane,
        children_cards: [card],
    });
    context.commit("cardIsHalfwayCreated");

    return card;
}

function linkCardToItsParent(
    context: ActionContext<SwimlaneState, RootState>,
    new_artifact_id: number,
    payload: NewCardPayload,
): Promise<void> {
    return pRetry(
        () =>
            tryToLinkCardToItsParent(context, new_artifact_id, payload).catch((error) => {
                if (error.response.status !== 412) {
                    throw new pRetry.AbortError(error);
                }

                throw error;
            }),
        retry_options,
    );
}

async function tryToLinkCardToItsParent(
    context: ActionContext<SwimlaneState, RootState>,
    new_artifact_id: number,
    payload: NewCardPayload,
): Promise<void> {
    const parent_artifact_response = await get(
        `/api/v1/artifacts/${encodeURIComponent(payload.swimlane.card.id)}`,
    );
    const { values } = await parent_artifact_response.json();

    const put_headers: Record<string, string> = { ...headers };
    const last_modified = parent_artifact_response.headers.get("Last-Modified");
    if (last_modified) {
        put_headers["If-Unmodified-Since"] = last_modified;
    }

    await put(`/api/v1/artifacts/${encodeURIComponent(payload.swimlane.card.id)}`, {
        headers: put_headers,
        body: JSON.stringify(
            getPutArtifactBodyToAddChild(
                payload,
                context.rootState.trackers,
                new_artifact_id,
                values,
            ),
        ),
    });
}

export async function loadPossibleAssignees(
    context: ActionContext<SwimlaneState, RootState>,
    tracker: Tracker,
): Promise<void> {
    if (
        tracker.assigned_to_field === null ||
        context.getters.have_possible_assignees_been_loaded_for_tracker(tracker)
    ) {
        return;
    }

    try {
        const response = await get(
            `/plugins/tracker/?func=get-values&formElement=${encodeURIComponent(
                tracker.assigned_to_field.id,
            )}`,
        );
        const users: User[] = await response.json();

        context.commit("setPossibleAssigneesForFieldId", {
            assigned_to_field_id: tracker.assigned_to_field.id,
            users: users.map(
                (user): UserForPeoplePicker => ({
                    ...user,
                    text: user.display_name,
                    id: Number(user.id),
                }),
            ),
        });
    } catch (error) {
        await context.dispatch("error/handleModalError", error, { root: true });
    }
}
