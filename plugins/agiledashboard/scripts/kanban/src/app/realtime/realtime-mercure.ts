import * as fetch from "@microsoft/fetch-event-source";
import { EventStreamContentType } from "@microsoft/fetch-event-source";

class RetriableError extends Error {}

class FatalError extends Error {}
export class RealtimeMercure {
    mercureEventSourceController: AbortController;
    token: string | null = null;
    url: string | null = null;
    lastid: string;
    eventDispatcher: (event: fetch.EventSourceMessage) => void;
    constructor(
        token: string,
        url: string,
        eventDispatcher: (event: fetch.EventSourceMessage) => void
    ) {
        this.token = token;
        this.mercureEventSourceController = new AbortController();
        this.url = url;
        this.eventDispatcher = eventDispatcher;
        fetch.fetchEventSource(this.url, this.buildEventSourceInit());
        this.lastid = "";
    }

    buildEventSourceInit(): fetch.FetchEventSourceInit {
        const eventDispatcher = this.eventDispatcher;
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
                throw new RetriableError();
            },
            onerror(err): void {
                if (err instanceof FatalError && err.message !== "401") {
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
