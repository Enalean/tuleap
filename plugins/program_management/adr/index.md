# Architectural Decision Log

This log lists the architectural decisions for Program Management plugin.

* [Glossary](glossary.md) - Definition of terms used in Program Management plugin

<!-- adrlog -- Regenerate the content by using `nix-shell -p nodePackages.npm --run 'npm exec --package=adr-log -- adr-log -e "+(template|glossary).md" -i'` -->

* [ADR-0001](0001-mirror-milestones.md) - Create Mirror Artifacts to filter what Teams can view
* [ADR-0002](0002-hexagonal-architecture.md) - Hexagonal Architecture in Program Management plugin
* [ADR-0003](0003-static-factory-method.md) - Static factory method
* [ADR-0004](0004-mock-free-tests.md) - Writing unit-tests without mocks

<!-- adrlogstop -->

ADRs that applies to plugins and core can be found [at the root of the project](../../../adr/index.md)

For new ADRs, please use [template.md](template.md) as basis.
More information on MADR is available at <https://adr.github.io/madr/>.
General information about architectural decision records is available at <https://adr.github.io/>.
