# Architectural Decision Log

This log lists the architectural decisions for Tuleap. You will find bellow the ADRs that applies to both Core and Plugins.

<!-- adrlog -- Regenerate the content by using `pnpm dlx adr-log -e template.md -i` -->

* [ADR-0001](0001-supported-browser-versions.md) - Supported browser list
* [ADR-0002](0002-ban-typescript-enum.md) - Ban TypeScript Enum syntax
* [ADR-0003](0003-favor-phpunit-mock-over-mockery.md) - Favor PHPUnit mock system over Mockery
* [ADR-0004](0004-tuleap-community-edition-docker-image.md) - Tuleap Community Edition docker image
* [ADR-0005](0005-forgeupgrade.md) - Database migrations with ForgeUpgrade
* [ADR-0006](0006-sign-docker-images.md) - Docker images signatures / Verify Docker images authenticity
* [ADR-0007](0007-js-package-manager.md) - JS package manager
* [ADR-0008](0008-cache-js-toolchain-build-results.md) - Caching of the build results of the JS toolchain
* [ADR-0009](0009-publish-js-lib-registry.md) - Publish JS libraries on a registry
* [ADR-0010](0010-ts-typechecking-individual-task.md) - TypeScript typechecking in individual task
* [ADR-0011](0011-js-framework.md) - State of JS frameworks in the Tuleap codebase
* [ADR-0012](0012-faults-over-exceptions.md) - Favor Faults over Exceptions
* [ADR-0013](0013-neverthrow.md) - NeverThrow
* [ADR-0014](0014-js-unit-test-runner.md) - JS unit test runner
* [ADR-0015](0015-mercure-realtime.md) - Replacing NodeJs realtime server with Mercure
* [ADR-0016](0016-frontend-libraries.md) - Independent libraries for shared frontend code
* [ADR-0017](0017-multiple-apps-per-context.md) - Multiple independent apps per context
* [ADR-0018](0018-js-bundler.md) - Choice of JavaScript Bundler
* [ADR-0019](0019-split-tlp.md) - Split `tlp` global library in small packages
* [ADR-0020](0020-repository-organization-tuleap-codebase.md) - Repository organization of the Tuleap codebase
* [ADR-0021](0021-attributes-based-events.md) - Usage of PHP attributes to declare listened hooks in plugins
* [ADR-0022](0022-option.md) - Option
* [ADR-0023](0023-outbound-http-requests) - Management of outbound HTTP requests (SSRF protection)
* [ADR-0024](0024-remove-ldap-write.md) - Remove LDAP write

<!-- adrlogstop -->

Plugins and libraries can also have their own ADRs:
* [GitLab](../plugins/gitlab/adr/glossary.md)
* [Program Management](../plugins/program_management/adr/index.md)
* [Roadmap](../plugins/roadmap/adr/index.md)
* [Tracker](../plugins/tracker/adr/index.md)
* [Artifact Modal](../plugins/tracker/scripts/lib/artifact-modal/adr/index.md)
* [Rich text editor](../plugins/tracker/scripts/lib/rich-text-editor/adr/index.md)
* [Lazybox](../lib/frontend/lazybox/adr/index.md)
* [Pullrequest](../plugins/pullrequest/adr/index.md)

For new ADRs, please use [template.md](template.md) as basis.
More information on MADR is available at <https://adr.github.io/madr/>.
General information about architectural decision records is available at <https://adr.github.io/>.
