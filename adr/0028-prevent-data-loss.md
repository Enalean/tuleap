# Strategies to mitigate risks of data loss in database

* Status: accepted
* Deciders: Marie-Ange GARNIER, Thomas GERBET, Thomas GORKA, Manuel VACELET, Joris MASSON
* Date: 2024-04-11

Technical Story: [request #37592 Mitigate risks of data loss in DB][2]

## Context and Problem Statement

In July 2023 we merged a [code][0] that contained a bug regarding the deletion of artifact values. This bug went unnoticed
for [8 months][1]. As the code was deleting values "elsewhere" (not in the current thing being deleted), it was not possible
to catch it during development and/or review.

## Considered Options

What could have been done to prevent such bug:

* Have an integration test about deletion
* Rely on UUIDs instead of auto incremented ids
* Have more thorough review process
* Have Foreign Keys to ensure data integrity
* Have tooling to verify data integrity
* Notarize artifact updates

## Decision Outcome

New code should use UUIDs instead of auto incremented ids. For reference see what was done on [FTS DB plugin][3]

In addition to that, we are going to investigate what kind of tooling can be developed to verify data integrity, for existing code

## Pros and Cons of the Options

### Have an integration test about deletion

The submitted code was not covered by an integration or unit test. Adding a test could have helped to spot the issue.

* Good, because we already have the infrastructure to run the tests.
* Good, because it's a good practice to have tests for the written code.
* Bad, because due to the nature of data set in test (low id numbers) it's possible that deletion could have worked silently.
* Bad, because we don't mandate tests to be written for each and every submitted patch.
* Bad, because the only way we could have the guaranty that the test is correct is if the code was written following TDD principles, but we cannot enforce that.
* Bad, because the granularity of tests in this area of code makes it hard to write tests that last (test setup would be very complex to write and maintain).

Conclusion: more testing would have helped, but it's not bulletproof enough to rely only on that.

### Rely on UUIDs instead of auto incremented ids

The issue exists because we delete based on `changeset_value_id` but we give a `changeset_id` as parameter. As both fields
are integer with auto incremented ids, there is an overlap, and it can delete something.

* Good, because UUIDs are conflict-free, so we cannot delete something "by mistake".
* Good, because not having sequential ids is a good security practice (avoid enumerations).
* Good, because it's a common alternative to auto incremented ids (or sequence) in the industry.
* Bad, because UUIDs are larger than integers. They take more disk storage and risks of cache eviction are higher, hence degradation of performances.
* Bad, because retrofitting UUIDs in current schema is a hard task.
* Bad, because we have to maintain a "human compatible id" for some features (e.g. cross-references).

Conclusion: even if not ideal, UUIDs are the only way to guarantee that this class of problem will no longer exist in the future.
However, it's a long and tough road, so we will start by implementing it for new code only to gain experience before retrofitting.

### Have a more thorough review process

The issue was not spotted during review, maybe we should have thrown more eyes to the patch.

* Good, because more reviews increases the likelihood of catching the issue.
* Bad, because unless the reviewer does look for this specific class of issues, it's almost impossible to catch.

Conclusion: we need to find a strategy that is more effective and doesn't rely mostly on chance.

### Have Foreign Keys to ensure data integrity

Having Foreign Keys (FK) on the database schema would have permitted writing a single delete statement "ON DELETE CASCADE"

* Good, because data integrity is under MySQL responsibility.
* Bad, because FK constraints have significant cost at insert/update, esp when there are a lot of big tables interlinked (e.g., in Trackers).
* Bad, because the risks are high if mistakes are made "ON DELETE CASCADE".
* Bad, because FK cannot be retrofitted on the existing schema (creation & updates have not always been in transactions).
* Bad, because if we miss a FK the whole thing is useless.

Conclusion: while appealing in theory, in practice, the cost of introducing FK now is too high.

### Have tooling to verify data integrity

In the same spirit as Foreign Keys, we could have had a tool that verifies that the database invariant is always respected.

* Good, because it's a tool that verify the constraints instead of relying on developer's or reviewer ability to catch the error.
* Good, because this kind of tool can be run in test suites (REST, E2E) to catch errors early.
* Bad, because running this kind of tool on production data is likely to be very resource-intensive and flag too many false-positives.
* Bad, because writing this kind of tool is Hard(tm)(c) to be right.
* Bad, because the tool must evolve with the codebase to be relevant in the future.

Conclusion: consistency checks after high-level tests suite is an interesting approach to address the existing code. However,
it cannot be the unique answer as it will require updates in the future, and we cannot guarantee that it will be updated.

### Notarize artifact updates

All artifact modifications (create, update, delete) should be streamed in an append-only storage, so we can rebuild the
current state from this form of backup.

* Good, because it's a guaranty that modifications cannot be altered in any way.
* Bad, because it requires a high level of engineering to introduce the concept.
* Bad, because it implies using new kind of tools for append-only storage.
* Bad, because given Tuleap data format, backup could be enough.

Conclusion: while appealing in theory, the amount of engineering needed at Tuleap level vs the likeliness than any user
will actually deploy what is needed for this to work, is not worth it.

## Links

[0]: https://tuleap.net/plugins/git/tuleap/tuleap/stable?a=blobdiff&h=1b56e723e58dcc6e4a02c9470228957ac115cf5a&hp=0000000000000000000000000000000000000000&hb=7f2e5e974596196bd96c2146c533353ecbcc592f&f=plugins%2Ftracker%2Finclude%2FTracker%2FArtifact%2FArtifactsDeletion%2FArtifactChangesetValueDeletorDAO.php
[1]: https://tuleap.net/plugins/tracker/?aid=37545
[2]: https://tuleap.net/plugins/tracker/?aid=37592
[3]: https://gerrit.tuleap.net/c/tuleap/+/30921
