# Publish JS libraries on a registry

* Status: accepted
* Deciders: Thomas Gerbet
* Date: 2022-01-10

## Context and Problem Statement

Tuleap uses private JS libraries to share code between its different apps. To interop with external applications or just
to share code with the rest of world it might be needed to publish our packages to a public registry. This document
describes the process and sets the rules for publishing a package.

## Base rules

* Packages are only published on the [npmjs.com registry](https://www.npmjs.com/)
* Packages can only be published under the [`tuleap` organization](https://www.npmjs.com/org/tuleap), to put it another
way the package name must be prefixed by `@tuleap/` (it is already the case for internal packages to avoid typosquatting)
* Packages versioning follow [SemVer](https://semver.org/) to be consistent with the rest of the JS ecosystem. Current
Tuleap versions has no impact on the version used for a JS package
* Only Tuleap maintainers can approve the publication of a new version of a JS package
* It is possible to determine from which commit a version of a JS package has been built
* The build and publication of the JS libraries are handled through an automated pipeline

## Expected publication process

1. Increment/Set the new version of the JS package to release, follow [SemVer](https://semver.org/) rules
2. Push to review this change like any other changes
3. Once approved and submitted, tag the commit with `PACKAGE_NAME~PACKAGE_VERSION` e.g. for a package called
`@tuleap/example` and a `1.0.1` your tag should be `@tuleap/example~1.0.1`
4. Publish the tag, automation process should be triggered to build and publish the package
