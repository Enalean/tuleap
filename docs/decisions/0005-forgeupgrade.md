# Database migrations with ForgeUpgrade

* Status: accepted
* Deciders: Manuel VACELET, Thomas GERBET, Nicolas TERRAY
* Date: 2021-06-25

Technical Story: [request #21864 Import forgeupgrade into Tuleap][0]

## Context and Problem Statement

Since it's inception (and even before) Tuleap relies on ForgeUpgrade to manage the migration of the database from one
version to another. Strongly inspired by the migrations bought by Ruby on Rails ForgeUpgrade served us well for more than
a decade. It was designed initially as an external tool when there was illusion that some sharing with other PHP forges
was possible. It's only being used by Tuleap and being an external tool is a PITA:
- All buckets are broken from an IDE or static analysis point of view because they inherit from undefined classes
- It's impossible to remember the API provided by the tool
- It's impossible to properly fix the API when needed
- It's un-necessary hard to manage upgrade of PHP versions
- It's un-necessary hard for the build system

## Considered Options

* [option 1] Internalize ForgeUpgrade as part of Tuleap API
* [option 2] Use doctrine-migration

## Decision Outcome

Chosen option: "[option 1] Internalize ForgeUpgrade as part of Tuleap API", because option 2 is not worth the change.

### Positive Consequences

* Tuleap build system simplified
* PHP upgrades like a breeze
* Tuleap devs already used to format
* Tuleap devs can now leverage the API & features

### Negative Consequences

* Tuleap relies on an internal, non-standard component.
* We have to maintain the internal component.

## Pros and Cons of the Options

### [option 1] Internalize ForgeUpgrade as part of Tuleap API

* Good, because Tuleap developers already knows how to write buckets
* Good, because the installation and upgrade system doesn't change
* Good, because it will make easier to clean-up and integrate Tuleap and ForgeUpgrade
* Bad, it's a missed opportunity to remove code to maintain ourselves

### [option 2] Use doctrine-migration

[Doctrine migration](https://www.doctrine-project.org/projects/migrations.html) is a PHP tool to manage change of databases.
It can be used without using Doctrine as ORM or DBAL and provide lot's of features around database migrations.

* Good, it's a battle tested tool from a well known community with top support
* Good, it's very close to what we already know in term of process with ForgeUpgrade
* Bad, because we need to manage the transition between the two systems
* Bad, there are a lot of features we don't want (downgrade, other DB supports, etc)
* Bad, we need to develop our own wrapper to hide and restrict the un-wanted features
* Bad, the way it's built doesn't play nice with our plugin system and requires [dirty hacks](https://gerrit.tuleap.net/c/tuleap/+/21274/7/src/common/DB/Migrations/MigrationsAtPluginInstallation.php#51)

[0]: https://tuleap.net/plugins/tracker/?aid=21864
