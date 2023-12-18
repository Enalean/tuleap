# @tuleap/plugin-pullrequest-stub

Provides a builder to stub PullRequests.

It contains three methods to help you build open/merged/abandoned pull-requests for unit-testing purpose:

``` TypeScript
type StubPullRequest = {
    buildOpenPullRequest(optional_data: Partial<PullRequestStubData>): PullRequestInReview,
    buildMergedPullRequest(optional_data: Partial<PullRequestStubData>): PullRequestMerged,
    buildAbandonedPullRequest(optional_data: Partial<PullRequestStubData>): PullRequestAbandoned,
};
```

You can pass custom properties using an `Option`:

``` TypeScript
import { PullRequestStub } from "@tuleap/plugin-pullrequest-stub";

const an_open_pull_request = PullRequestStub.buildOpenPullRequest(
    { title: "My custom pull-request title" },
);

const a_merged_pull_request = PullRequestStub.buildMergedPullRequest();
```
