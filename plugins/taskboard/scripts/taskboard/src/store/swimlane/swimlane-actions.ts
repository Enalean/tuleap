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

import { Card, ColumnDefinition, Swimlane } from "../../type";
import { recursiveGet, patch, get } from "tlp";
import { ActionContext } from "vuex";
import {
    SwimlaneState,
    MoveCardsPayload,
    ReorderCardsPayload,
    RefreshParentCardActionPayload,
    RefreshParentCardMutationPayload
} from "./type";
import { RootState } from "../type";
import { UserPreference, UserPreferenceValue } from "../user/type";

export * from "./drag-drop-actions";
export * from "./card/card-actions";

export async function loadSwimlanes(
    context: ActionContext<SwimlaneState, RootState>
): Promise<void> {
    context.commit("beginLoadingSwimlanes");
    try {
        await recursiveGet(`/api/v1/taskboard/${context.rootState.milestone_id}/cards`, {
            params: {
                limit: 100
            },
            getCollectionCallback: (collection: Card[]): Swimlane[] => {
                const is_loading_children_cards = false;
                const is_in_edit_mode = false;
                const children_cards: Card[] = [];

                const swimlanes = collection.map(card => {
                    const { remaining_effort } = card;
                    if (remaining_effort) {
                        remaining_effort.is_being_saved = false;
                        remaining_effort.is_in_edit_mode = false;
                    }

                    return {
                        card: { ...card, remaining_effort, is_in_edit_mode },
                        children_cards,
                        is_loading_children_cards
                    };
                });
                context.commit("addSwimlanes", swimlanes);
                swimlanes
                    .filter(swimlane => swimlane.card.has_children)
                    .map(swimlane_with_children =>
                        context.dispatch("loadChildrenCards", swimlane_with_children)
                    );

                return swimlanes;
            }
        });
    } catch (error) {
        await context.dispatch("error/handleGlobalError", error, { root: true });
    } finally {
        context.commit("endLoadingSwimlanes");
    }
}

export async function loadChildrenCards(
    context: ActionContext<SwimlaneState, RootState>,
    swimlane: Swimlane
): Promise<void> {
    context.commit("beginLoadingChildren", swimlane);
    try {
        const card_id = swimlane.card.id;
        await recursiveGet(`/api/v1/taskboard_cards/${card_id}/children`, {
            params: {
                milestone_id: context.rootState.milestone_id,
                limit: 100
            },
            getCollectionCallback: (collection: Card[]): Card[] => {
                collection.forEach(card => {
                    card.is_in_edit_mode = false;
                    if (card.remaining_effort) {
                        card.remaining_effort.is_being_saved = false;
                        card.remaining_effort.is_in_edit_mode = false;
                    }
                });
                context.commit("addChildrenToSwimlane", {
                    swimlane,
                    children_cards: collection
                });
                return collection;
            }
        });
    } catch (error) {
        await context.dispatch("error/handleModalError", error, { root: true });
    } finally {
        context.commit("endLoadingChildren", swimlane);
    }
}

export function expandSwimlane(
    context: ActionContext<SwimlaneState, RootState>,
    swimlane: Swimlane
): Promise<void> {
    context.commit("expandSwimlane", swimlane);
    const payload: UserPreference = {
        key: getPreferenceName(context, swimlane)
    };

    return context.dispatch("user/deletePreference", payload, { root: true });
}

export function collapseSwimlane(
    context: ActionContext<SwimlaneState, RootState>,
    swimlane: Swimlane
): Promise<void> {
    context.commit("collapseSwimlane", swimlane);
    const payload: UserPreferenceValue = {
        key: getPreferenceName(context, swimlane),
        value: "1"
    };

    return context.dispatch("user/setPreference", payload, { root: true });
}

function getPreferenceName(
    context: ActionContext<SwimlaneState, RootState>,
    swimlane: Swimlane
): string {
    return `plugin_taskboard_collapse_${context.rootState.milestone_id}_${swimlane.card.id}`;
}

export async function reorderCardsInCell(
    context: ActionContext<SwimlaneState, RootState>,
    payload: ReorderCardsPayload
): Promise<void> {
    try {
        const url = getPATCHCellUrl(payload.swimlane, payload.column);

        await patch(url, {
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ order: payload.position })
        });

        context.commit("changeCardPosition", payload);
    } catch (error) {
        await context.dispatch("error/handleModalError", error, { root: true });
    }
}

export async function moveCardToCell(
    context: ActionContext<SwimlaneState, RootState>,
    payload: MoveCardsPayload
): Promise<void> {
    try {
        const url = getPATCHCellUrl(payload.swimlane, payload.column);
        const body = getMoveCardBody(payload);

        await patch(url, {
            headers: {
                "Content-Type": "application/json"
            },
            body
        });

        context.dispatch("refreshParentCard", { swimlane: payload.swimlane });
        context.commit("moveCardToColumn", payload);
    } catch (error) {
        await context.dispatch("error/handleModalError", error, { root: true });
    }
}

export async function refreshParentCard(
    context: ActionContext<SwimlaneState, RootState>,
    payload: RefreshParentCardActionPayload
): Promise<void> {
    const card_id = payload.swimlane.card.id;

    try {
        const response = await get(`/api/v1/taskboard_cards/${card_id}`, {
            params: { milestone_id: context.rootState.milestone_id }
        });
        const refreshed_card = await response.json();

        const mutation_payload: RefreshParentCardMutationPayload = {
            swimlane: payload.swimlane,
            parent_card: refreshed_card
        };
        context.commit("refreshParentCard", mutation_payload);
    } catch (error) {
        await context.dispatch("error/handleModalError", error, { root: true });
    }
}

function getMoveCardBody(payload: MoveCardsPayload): string {
    const body = {
        add: payload.card.id
    };

    if (payload.position) {
        Object.assign(body, { order: payload.position });
    }

    return JSON.stringify(body);
}

function getPATCHCellUrl(swimlane: Swimlane, column: ColumnDefinition): string {
    const swimlane_id = swimlane.card.id;
    const column_id = column.id;

    return `/api/v1/taskboard_cells/${encodeURIComponent(swimlane_id)}/column/${encodeURIComponent(
        column_id
    )}`;
}
