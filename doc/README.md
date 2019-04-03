# Backend

## Data model

![Data model](data_model.png)

## Components architecture

![Components architecture](backend_architecture.png)

**Hexagonal architecture** is followed here in order to limit technical debt on legacy code to corrupt baseline code base. This allows to keep baseline codebase testable as much as it can be (except for adapters, see below).

More over, this architecture allows to follow some **Domain Driven Design** principles which make code easier to understand, therefore to maintain.

This way, codebase is splitted in 3 main namespaces:

- **REST**: this namespace exposes domain as REST services with handling boilerplate and technical stuff (Restler configuration, input parsing and validation, output serialisation, exceptions convertion...).

- **Domain**: this namespace represents the domain of baseline plugin. It explains what this plugin do for the domain. It should not depends on any external library or legacy code. Interfaces are used to reverse dependency.

- **Adapter**: this an anti-corruption layer which prevent legacy code to corrupt baseline codebase. So, this namespace is potentialy the most difficult to test. It is also used to abstract infrastructure (ex: database) in order to prevent technical code to invade domain.


## Security policy

### Authentication

This step is handled by **resources** (in REST namespace)

### Authorization

Each **repository** is responsible for this step. This way, an unauthorized resource is seen as unexisting.
This implies to transit current user from REST namespace to these repositories as function argument.

# Appendices
Diagrams are created with [yEd live](https://www.yworks.com/yed-live/).
