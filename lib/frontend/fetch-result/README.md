# @tuleap/fetch-result

A fork of `@tuleap/tlp-fetch` that returns `ResultAsync` variants from [neverthrow][neverthrow] instead of Promises.

It also automatically encodes all URIs passed to it. It sets "Content-Type" header to JSON and automatically encodes payload to JSON in the Request body for write operations (post, put, patch). Whenever there is a failure, it _does not throw Errors_. It returns a [Fault][fault] in an `Err` variant of `ResultAsync`. This better communicates that the method can fail.

It also handles better Tuleap error responses. See the [Faults](#faults) section below.

## Usage

### `getJSON()`

```typescript
import type { ResultAsync } from "@neverthrow";
import type { Fault } from "@tuleap/fault";
import { getJSON, uri } from "@tuleap/fetch-result";

type User = {
    readonly id: number | null;
    readonly real_name: string;
    readonly username: string;
};

// searchUser("jdoe", 10, 0) will query /api/v1/users/?query%3D%7Busername%3A%22jdoe%22%7D&limit=10&offset=0
function searchUser(username: string, limit: number, offset: number): ResultAsync<User, Fault> {
    return getJSON<User>(uri`/api/v1/artifacts/${id}`, {
        params: {
            // These parameters are URI-encoded and appended to the base URI
            query: JSON.stringify({ username }),
            limit,
            offset
        }
    });
}
```

### `head()`

```typescript
import type { ResultAsync } from "@neverthrow";
import type { Fault } from "@tuleap/fault";
import { head, uri } from "@tuleap/fetch-result";

// getBacklogSize(20) will query /api/v1/kanban/20/backlog?query%3D%7Bstatus%3A%22open%22%7D
function getBacklogSize(kanban_id: number): ResultAsync<number, Fault> {
    return head(uri`/api/v1/kanban/${kanban_id}`, {
        params: {
            // These parameters are URI-encoded and appended to the base URI
            query: JSON.stringify({ status: "open" })
        }
    }).map((response) =>
        Number.parseInt(response.headers.get("X-PAGINATION-SIZE"), 10)
    );
}
```

### `options()`

```typescript
import type { ResultAsync } from "@neverthrow";
import type { Fault } from "@tuleap/fault";
import { options, uri } from "@tuleap/fetch-result";

type Quota = {
   readonly disk_quota: number;
   readonly disk_usage: number;
   readonly max_chunk_size: number;
};

function getFileUploadRules(): ResultAsync<Quota, Fault> {
    return options(uri`/api/v1/artifact_temporary_files`)
        .map((response) => {
            const disk_quota = Number.parseInt(response.headers.get('X-QUOTA'), 10);
            const disk_usage = Number.parseInt(response.headers.get('X-DISK-USAGE'), 10);
            const max_chunk_size = Number.parseInt(response.headers.get('X-UPLOAD-MAX-FILE-CHUNKSIZE'), 10);

            return { disk_quota, disk_usage, max_chunk_size };
        });
}
```

### `putJSON()`

```typescript
import type { ResultAsync } from "@neverthrow";
import type { Fault } from "@tuleap/fault";
import { putJSON, uri } from "@tuleap/fetch-result";

type UpdatedReport = {
    readonly report_id: number;
};

function updateReport(report_id: number, trackers_id: number): ResultAsync<UpdatedReport, Fault> {
    // "Content-Type" header is automatically set to "application/json"
    // The second parameter is automatically encoded to JSON string in the Request body
    return putJSON<UpdatedReport>(uri`/api/v1/cross_tracker_reports/
    ${report_id}`, { trackers_id });
}
```

### `patchJSON()`

```typescript
import type { ResultAsync } from "@neverthrow";
import type { Fault } from "@tuleap/fault";
import { patchJSON, uri } from "@tuleap/fetch-result";

type UpdatedLabel = {
    readonly label_id: number;
};

function removeLabel(label_id: number): ResultAsync<UpdatedLabel, Fault> {
    // "Content-Type" header is automatically set to "application/json"
    // The second parameter is automatically encoded to JSON string in the Request body
    return patchJSON<UpdatedLabel>(uri`/api/v1/labels`, { remove: [{id: label_id }]});
}
```

### `postJSON()`

```typescript
import type { ResultAsync } from "@neverthrow";
import type { Fault } from "@tuleap/fault";
import { postJSON, uri } from "@tuleap/fetch-result";

type CreatedArtifact = {
    readonly artifact_id: number;
};

function createArtifact(tracker_id: number, field_values: unknown): ResultAsync<CreatedArtifact, Fault> {
    // "Content-Type" header is automatically set to "application/json"
    // The second parameter is automatically encoded to JSON string in the Request body
    return postJSON<CreatedArtifact>(uri`/api/v1/artifacts`, { tracker: { id: tracker_id }, values: field_values });
}
```

### `post()`

```typescript
import type { ResultAsync } from "@neverthrow";
import type { Fault } from "@tuleap/fault";
import { postJSON, uri } from "@tuleap/fetch-result";

function createArtifact(tracker_id: number, field_values: unknown): ResultAsync<Response, Fault> {
    // "Content-Type" header is automatically set to "application/json"
    // The second parameter is automatically encoded to JSON string in the Request body
    return post(uri`/api/v1/artifacts`, { tracker: { id: tracker_id }, values: field_values });
}
```

### `del()`

```typescript
import type { ResultAsync } from "@neverthrow";
import type { Fault } from "@tuleap/fault";
import { del, uri } from "@tuleap/fetch-result";

function removeUser(user_id: number): ResultAsync<Response, Fault> {
    return del(uri`/api/v1/users/${user_id}`);
}
```

### `getAllJSON()`

```typescript
import type { ResultAsync } from "@neverthrow";
import type { Fault } from "@tuleap/fault";
import { getAllJSON, uri } from "@tuleap/fetch-result";

type Project = {
    readonly id: number;
    readonly shortname: string;
    readonly label: string;
};

type ProjectCollection = {
    collection: ReadonlyArray<Project>;
};

// On each request, getAllJSON will call this callback with the JSON payload
function getCollectionCallback({ collection }: ProjectCollection): ReadonlyArray<Project> {
    // You can also leverage this callback to display this batch of items
    displayABatchOfTrackers(collection);

    // collection must be an [Array]
    return collection;
}

function getTrackersOfProject(project_id: number): ResultAsync<ReadonlyArray<Project>, Fault> {
    return getAllJSON<Project, ProjectCollection>(uri`/api/v1/projects/${project_id}/trackers`, {
        params: {
            // These parameters are URI-encoded and appended to the base URI
            limit: 50,
            query: JSON.stringify({ is_open: true })
        },
        getCollectionCallback,
        max_parallel_requests: 6,
    });
}
```

<span id="faults"></span>
## Faults

If there is a problem (network error, remote API error, JSON parsing error), it returns an `Err` variant containing a [Fault][fault]. Contrary to `@tuleap/tlp-fetch` where everybody had to import `FetchWrapperError`, thus increasing the dependencies on the lib, the Faults returned by this library are voluntarily opaque. There is no subtype to discourage the use of `instanceof` operator.

All Faults have a special method to distinguish them if needed:

* Network error: `"isNetworkFault" in fault && fault.isNetworkFault() === true`
* JSON parsing error: `"isJSONParseFault" in fault && fault.isJSONParseFault() === true`
* Tuleap API error: `"isTuleapAPIFault" in fault && fault.isTuleapAPIFault() === true`
* HTTP 403 Error code: `"isForbidden" in fault && fault.isForbidden() === true`
* HTTP 404 Error code: `"isNotFound" in fault && fault.isNotFound() === true`

In the majority of cases, you will not even need to distinguish them. Any Fault indicates that there has been a problem, and something should be displayed for the end-user.

Contrary to `@tuleap/tlp-fetch`, this library automatically reads the JSON body of an error response and tries to read its `error.i18n_error_message` or its `error.message` properties. It creates Tuleap API faults with the error message. If none of those properties are found, it creates a Tuleap API fault with the Response's `statusText`. To display that error message, you can cast the Fault to String: `String(fault)`.

[neverthrow]: https://github.com/supermacro/neverthrow
[fault]: ../fault/README.md
