# @tuleap/tlp-fetch

## Prerequisites

To get access to `@tuleap/tlp-fetch` you will need to install it with the JS package manager.

## Get

(@tuleap/tlp-fetch).get(url_to_query, options)

### Arguments:

* { String } URL to query
* (Optional) { Object } options. Contains:
    * (Optional) [Fetch init options][0]
    * (Optional) { Object } params
      * Parameters to be added to the query URL.

To use get, import it: `import { get } from '@tuleap/tlp-fetch';`
You can pass parameters directly to Fetch, such as `{ cache: 'force-cache', ... }`. See [the request API][1].
All parameters in `{ params: { ... }}` will be URI-encoded. Common cases are to pass limit and offset for paginated routes.

Please note that you must `await response.json();` too !

```typescript
import { get } from '@tuleap/tlp-fetch';

// searchUser("jdoe", 10, 0) will query /api/v1/users/?query%3D%7Busername%3A%22jdoe%22%7D&limit=10&offset=0
async function searchUser(username, limit, offset) {
    const response = await get('/api/v1/users/', {
        cache: 'force-cache',
        params: {
            // These parameters are URI-encoded
            query: JSON.stringify({ username }),
            limit,
            offset
        }
    });
    const users = await response.json();

    return users;
}
```

## Patch

(@tuleap/tlp-fetch).patch(url_to_query, options)

### Arguments:

* { String } URL to query
* (Optional) { Object } [Fetch init options][0]

You can pass parameters directly to Fetch just like (@tuleap/tlp-fetch).get.

Passing parameters intended for the query URL using `{ params: { //... }}` is not supported.

```typescript
import { patch } from '@tuleap/tlp-fetch';

async function removeLabel(label_id) {
    const label = { id: label_id };

    return await patch('/api/v1/labels/', {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ remove: [label] })
    });
}
```

## Put

(@tuleap/tlp-fetch).put(url_to_query, options)

### Arguments:

* { String } URL to query
* (Optional) { Object } [Fetch init options][0]

You can pass parameters directly to Fetch just like (@tuleap/tlp-fetch).get.

Passing parameters intended for the query URL using `{ params: { //... }}` is not supported.

```typescript
import { put } from '@tuleap/tlp-fetch';

async function updateReport(report_id, trackers_id) {
    const response = await put(encodeURI("/api/v1/cross_tracker_reports/" + report_id), {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ trackers_id })
    });
    return await response.json();
}
```

## Post

(@tuleap/tlp-fetch).post(url_to_query, options)

### Arguments:

* { String } URL to query
* (Optional) { Object } [Fetch init options][0]

This function is very handy to validate forms asynchronously (ex: Forms inside modals).

You can pass parameters directly to Fetch just like (@tuleap/tlp-fetch).get.

Passing parameters intended for the query URL using `{ params: { //... }}` is not supported.

```typescript
import { post } from '@tuleap/tlp-fetch';

async function uploadFile() {
    const form_data = new FormData(document.getElementById('project-admin-user-import-form'));
    const response  = await post('userimport.php', {
        body: form_data
    });

    const json = await response.json();

    renderImportPreview(json);
}
```

## Del

(@tuleap/tlp-fetch).del(url_to_query, options

Performs a `DELETE` operation.

### Arguments:

* { String } URL to query
* (Optional) { Object } [Fetch init options][0]

You can pass parameters directly to Fetch just like (@tuleap/tlp-fetch).get.

Passing parameters intended for the query URL using `{ params: { //... }}` is not supported.

```typescript
import { del } from "@tuleap/tlp-fetch";

function removeUser(user_id) {
    return del(encodeURI(`/api/v1/users/${user_id}`));
}
```
## recursiveGet

(@tuleap/tlp-fetch).recursiveGet(url_to_query, options)

Use `recursiveGet` to query paginated collections (with limit and offset) and get all their items recursively.

### Arguments:

* { String } URL to query
* (Optional) { Object } options.

Contains:
* (Optional) [Fetch init options][0]
* { Object } params
    * Parameters to be added to the query URL. See (@tuleap/tlp-fetch).get. Contains:
      * { Number } limit
        * The number of items to query for each request. Defaults to 100.
      * { Number } offset
        * The offset from which to start the first request. Defaults to 0.
* (Optional) { Function } getCollectionCallback
  * After each request, `recursiveGet` will call this function with a single `json` argument containing the `response.json()`. The callback must return a `[Array]` containing the collection of items.

If not provided, the REST Endpoint must return an `[Array]`. By default `response.json()` will be coerced to `[Array]` using `[].concat(json)`.

```typescript
import { recursiveGet } from '@tuleap/tlp-fetch';

// On each request, recursiveGet will call this callback with the response.json()
function getCollectionCallback({ collection }) {
    // You can also leverage this callback to display this batch of items
    displayABatchOfTrackers(collection);

    // collection must be an [Array]
    return collection;
}

async function getTrackersOfProject(project_id) {
    return await recursiveGet(encodeURI(`/api/v1/projects/${project_id}/trackers`), {
        params: {
            // These parameters are JSON-encoded and URI-encoded
            limit: 50,
            query: { is_open: true }
        },
        getCollectionCallback
    });
}
```

## options

(@tuleap/tlp-fetch).options(url_to_query, options)

### Arguments:

* { String } URL to query
* (Optional) { Object } [Fetch init options][0]

This function is useful to get some information on a given REST route and (optionally) for a given query.

You can pass parameters directly to Fetch just like (@tuleap/tlp-fetch).get

Passing parameters intended for the query URL using `{ params: { //... }}` is not supported.

```typescript
import { options } from '@tuleap/tlp-fetch';

async function getFileUploadRules() {
    const route          = '/api/v1/artifact_temporary_files';
    const response       = await options(route);
    const disk_quota     = parseInt(response.headers.get('X-QUOTA'), 10);
    const disk_usage     = parseInt(response.headers.get('X-DISK-USAGE'), 10);
    const max_chunk_size = parseInt(response.headers.get('X-UPLOAD-MAX-FILE-CHUNKSIZE'), 10);

    return {
        disk_quota,
        disk_usage,
        max_chunk_size
    };
}
```

## head

(@tuleap/tlp-fetch).head(url_to_query, options)

Performs a `HEAD` operation. This function is useful to get the headers of a given REST route and (optionally) a given query.

### Arguments:

* { String } URL to query
* (Optional) { Object } options. Contains:
    * (Optional) [Fetch init options][0]
    * (Optional) { Object } params
      * Parameters to be added to the query URL.

To use get, import it: `import { head } from "@tuleap/tlp-fetch";`
You can pass parameters directly to Fetch, such as `{ cache: "force-cache", ... }`. See [the request API][1]
All parameters in `{ params: { ... }}` will be URI-encoded. Common cases are to pass limit and offset for paginated routes.

```typescript
import { head } from "@tuleap/tlp-fetch";

// getBacklogSize(20) will query /api/v1/kanban/20/backlog?query%3D%7Bstatus%3A%22open%22%7D
async function getBacklogSize(kanban_id) {
    const response = await head(encodeURI(`/api/v1/kanban/${kanban_id}/backlog`), {
        params: {
            // These parameters are URI-encoded
            query: JSON.stringify({ status: "open" }),
        },
    });
    return Number.parseInt(response.headers.get("X-PAGINATION-SIZE"), 10);
}
```

[0]: https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch
[1]: https://developer.mozilla.org/en-US/docs/Web/API/Request/Request
