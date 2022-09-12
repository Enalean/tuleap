/**
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

export interface StoppableQuery {
    readonly run: () => void;
    readonly stop: () => void;
}

interface DelayedQuerier {
    readonly cancelPendingQuery: () => void;
    readonly scheduleQuery: (callback: StoppableQuery) => void;
}

export const delayedQuerier = (): DelayedQuerier => {
    const THRESHOLD_TO_NOT_FLOOD_SERVER_IN_MS = 250;
    let already_scheduled_query: {
        stop: StoppableQuery["stop"];
        timeout_id: ReturnType<typeof setTimeout>;
    } | null = null;

    const cancelPendingQuery = (): void => {
        if (already_scheduled_query !== null) {
            clearTimeout(already_scheduled_query.timeout_id);
            already_scheduled_query.stop();
        }
    };

    const scheduleQuery = ({ stop, run }: StoppableQuery): void => {
        cancelPendingQuery();
        already_scheduled_query = {
            stop,
            timeout_id: setTimeout(run, THRESHOLD_TO_NOT_FLOOD_SERVER_IN_MS),
        };
    };

    return {
        cancelPendingQuery,
        scheduleQuery,
    };
};
