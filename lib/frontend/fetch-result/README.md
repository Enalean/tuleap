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
    // The remote API endpoint returns a User
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

### `getResponse()`

```typescript
import type { ResultAsync } from "@neverthrow";
import type { Fault } from "@tuleap/fault";
import { decodeJSON, getResponse, uri } from "@tuleap/fetch-result";

// getPersonalTimes(110, 20, 20) will query /api/v1/users/110/timetracking?query=%7Bstart_date%3A%222003-12-10T00%3A00%3A00Z%22%2Cend_date%3A%222003-12-17T00%3A00%3A00Z%22%7D&limit=20&offset=20
function getPersonalTimes(user_id: number, limit: number, offset: number): ResultAsync<PersonalTime[], Fault> {
    return getResponse(uri`/api/v1/users/${user_id}/timetracking`, {
        params: {
            // These parameters are URI-encoded and appended to the base URI
            query: JSON.stringify({
                start_date: "2003-12-10T00:00:00Z",
                end_date: "2003-12-17T00:00:00Z"
            }),
            limit,
            offset
        }
    }).andThen((response) => {
        // You can work on the headers of the Response, and later decode the body to JSON
        const total = Number.parseInt(response.headers.get("X-PAGINATION-SIZE") ?? "0", 10);
        return decodeJSON<PersonalTime[]>(response).map((times) => ({
            times,
            total,
        }));
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
        Number.parseInt(response.headers.get("X-PAGINATION-SIZE") ?? "0", 10)
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
    // The remote API endpoint returns an UpdatedReport
    return putJSON<UpdatedReport>(
        uri`/api/v1/cross_tracker_reports/${report_id}`,
        // These parameters are encoded to a JSON string in the Request body
        { trackers_id }
    );
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
    // The remote API endpoint returns an UpdatedLabel
    return patchJSON<UpdatedLabel>(
        uri`/api/v1/labels`,
        // These parameters are encoded to a JSON string in the Request body
        { remove: [{id: label_id }]}
    );
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
    // The remote API endpoint returns a CreatedArtifact
    return postJSON<CreatedArtifact>(
        uri`/api/v1/artifacts`,
        // These parameters are encoded to a JSON string in the Request body
        { tracker: { id: tracker_id }, values: field_values }
    );
}
```

### `postResponse()`

Note: `patchResponse` and `putResponse()` are also available and work the same way.

```typescript
import type { ResultAsync } from "@neverthrow";
import type { Fault } from "@tuleap/fault";
import { decodeJSON, postResponse, uri } from "@tuleap/fetch-result";

type ItemDefinition = {
    readonly xref: string;
    readonly title: string;
}

// searchAt("test", 20) will query /api/v1/search?limit=50&offset=20
function searchAt(keywords: string, offset: number): ResultAsync<ItemDefinition[], Fault> {
    // "Content-Type" header is automatically set to "application/json"
    return postResponse(
        uri`/api/v1/search`,
        // These parameters are URI-encoded and appended to the base URI
        { params: { limit: 50, offset } },
        // These parameters are encoded to a JSON string in the Request body
        { search_query: { keywords } },
    ).map((response) => {
        // For example, you can work on the headers of the Response, and later decode the body to JSON
        return decodeJSON<ItemDefinition[]>(response);
    });
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

// For each request, getAllJSON will call this callback with the JSON payload
function getCollectionCallback({ collection }: ProjectCollection): ReadonlyArray<Project> {
    // You can also leverage this callback to display this batch of items
    displayABatchOfTrackers(collection);

    // collection must be an Array or ReadonlyArray
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
