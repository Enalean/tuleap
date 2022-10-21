# @tuleap/plugin-git-gitlab-api-querier

Makes it easier to query the GitLab API.

## Faults

If there is a problem (network error, credentials rejected, lack of permissions), it returns an `Err` variant containing a [Fault][fault]. The Faults returned by this library are voluntarily opaque. There is no subtype to discourage the use of `instanceof` operator.

All Faults have a special method to distinguish them if needed:

* Network error: `"isNetworkFault" in fault && fault.isNetworkFault() === true`
* GitLab API error: `"isGitlabAPIFault" in fault && fault.isGitlabAPIFault() === true`
* HTTP 401 Error code: `"isUnauthenticated" in fault && fault.isUnauthenticated() === true`

[fault]: ../../../../../lib/frontend/fault/README.md
