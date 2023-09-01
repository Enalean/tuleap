/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
import * as fetch from "@microsoft/fetch-event-source";
import { EventStreamContentType } from "@microsoft/fetch-event-source";

export class RetriableError extends Error {}

export class FatalError extends Error {}
export class RealtimeMercure {
    mercureEventSourceController: AbortController;
    token: string | null = null;
    url: string | null = null;
    lastid: string;
    eventDispatcher: (event: fetch.EventSourceMessage) => void;
    errCallback: (err?: Error) => void;
    sucessCallback: () => void;

    constructor(
        token: string,
        url: string,
        eventDispatcher: (event: fetch.EventSourceMessage) => void,
        errorCallback: (err?: Error) => void,
        sucessCallback: () => void,
    ) {
        this.token = token;
        this.mercureEventSourceController = new AbortController();
        this.url = url;
        this.eventDispatcher = eventDispatcher;
        this.errCallback = errorCallback;
        this.sucessCallback = sucessCallback;
        this.lastid = "";
        fetch.fetchEventSource(this.url, this.buildEventSourceInit());
    }

    buildEventSourceInit(): fetch.FetchEventSourceInit {
        const eventDispatcher = this.eventDispatcher;
        const errorCallback = this.errCallback;
        const sucessCallback = this.sucessCallback;
        return {
            headers: {
                Authorization: "Bearer " + this.token,
                "Last-Event-ID": this.lastid,
            },
            signal: this.mercureEventSourceController?.signal,
            openWhenHidden: true,
            onmessage(event): void {
                eventDispatcher(event);
            },
            onopen(response): Promise<void> {
                if (
                    response.ok &&
                    response.headers.get("content-type") === EventStreamContentType
                ) {
                    sucessCallback();
                    return new Promise<void>((resolve) => resolve());
                } else if (
                    response.status >= 400 &&
                    response.status < 600 &&
                    response.status !== 429
                ) {
                    throw new FatalError();
                } else {
                    throw new RetriableError(response.status.toString());
                }
            },
            onclose(): void {
                errorCallback(new RetriableError());
            },
            onerror(err): void {
                errorCallback(err);
                if (err instanceof FatalError) {
                    throw err;
                }
            },
        };
    }

    abortConnection(): void {
        this.mercureEventSourceController.abort();
    }

    editToken(token: string): void {
        this.token = token;
        this.mercureEventSourceController.abort();
        this.mercureEventSourceController = new AbortController();
        if (this.url !== null) {
            fetch.fetchEventSource(this.url, this.buildEventSourceInit());
        }
    }
}
