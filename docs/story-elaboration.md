# Feature lifecycle

A feature to be developed must be first described either as:
* an [epic](https://tuleap.net/kanban/49), when a feature is large enough to span a release or more,
* a [story](https://tuleap.net/kanban/74), when the feature is a sub-part of an epic or small enough to not deserve an epic,
* a [request](https://tuleap.net/plugins/tracker/?tracker=140), for bugs, technical enhancements or small features.

In this document we will refer to these 3 options as Change Request.

Ultimately, the size of the feature is an indicator and there are no formal differences between the 3 options. However,
all contributions must have a public-facing reference in one of those 3 options. The link between the contribution is
done with a reference in the commit message, see [how to submit a patch](patches.md) for reference.

An Epic or a Story can be created by a Contributor or an Integrator (see [Readme](../README.md) for the list). A Request
can be created by any registered user on tuleap.net. Only Integrators can decide if a given Change Request can be selected
for implementation.

Tuleap Community Edition is a rolling release developed in Trunk-Based Development. Each commit leads to a new version,
there are no maintenance branches of published versions. Every 4 weeks, a new tag is published to announce the Change
Requests that were implemented during the elapsed time and that are ready to use.

## Change Request reviews

When a Change Request is submitted by a Contributor or a registered user, an Integrator reviews the request. If the Change Request is added to the Backlog or if an Integrator integrates a contribution related to a Change Request, it indicates that the Change Request has been approved.

## Change Request guidelines

Change Request must follow these principles during the design and implementation phases:

### Security by design

Tuleap already comes with a quite complete and complex permission system. Change Request designers must ensure  permission
needs were evaluated:
* is there any existing permission scheme on the data that must be enforced ?
* if there is no existing permission, is it mandatory to introduce a new one ? Start by re-using an existing permission
  and only introduce a new one if deemed necessary (for instance, start by associating a feature to Project Administrators, and create
  a new permission if end users report that they want to assign the permission to someone that cannot be Project Administrator).
  Adding a new permission introduces a new level of complexity from user and developer point of view and should be done only when deemed necessary.
* do not introduce a new permission scheme in order to respect the **principle of least surprise**.

Implementation must follow [Secure Coding Practices](./secure-coding-practices.md) in order to follow **defense in depth**
principles.

### Privacy by design

Only collect Personally Identifiable Information when strictly needed. If a new PII shall be collected, it must be
documented with risk assessment in case of leak. When relevant, PII shall be encrypted using the cryptographic tools specified in the [Secure Coding Practices](./secure-coding-practices.md).

However, by design, Tuleap is meant to keep records on very long time period (data retention for long-term product development
in this industry). The data retention and data erasure cannot be under the responsibility of the end user but shall be requested to
an administrator who can apply requester rights according to the organisation needs.

### Feature consistency and cohesion

New features shall complete or improve the functional coverage of Tuleap.

Each new Change Request shall be evaluated with Integration (of a tool that already does what we want) vs. Make (
implement natively the feature).

## Change Request validation

Change Requests are developed following Trunk-based development principles. The validation of each Change Request chunk (commit) shall
be done by an integrator following the integration guidelines described in [Integration rules](patches.md) section.

## Change Request release

Each Change Request chunk (commit) is released as soon as it is integrated in the main development branch.
