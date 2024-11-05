# Create Mirror Artifacts to filter what Teams can view

* Status: accepted
* Deciders: Joris MASSON, Manuel VACELET
* Date: 2021-05-24

Technical Story: [epic #16683 Program Management][0]

## Context and Problem Statement

See the [glossary][1] for definition of terms.

In Program Management, a Program Manager (in a Program project) creates Program Increments. He/she then plans Features
in Program Increments. The Teams linked to the Program can also see the Program Increments, but they don't see the
Features. They see the User Stories children of (linked with `_is_child`) those Features. More precisely, they don't
see all children of Features, they only see User Stories belonging to their own Team. The point is rather, they
**don't** see Requirements, or Tasks, or whatever other type of Artifacts from _other Teams_.

Additionally, in Program Management, the Program Manager splits Program Increments into Iterations. Then, in each Team,
the _Team Manager_ (or appropriate role) plan User Stories from the Program Increment's backlog into Iterations. Then,
in the Program project, the Program Manager can see what Teams planned in each Iteration.

The same "filtering" problem exists for Iterations. In Teams, Team Managers will plan their User Stories and should see all User
Stories relevant to their own Team. They **must not** see other Teams' User Stories (or Requirements or Tasks, etc.).

In the Program project, a dedicated application will be available. In Teams however, the usual Tuleap Agile Dashboard
services (Planning View, Kanban, Overview page, Project Milestones widget) will be used.

How can we filter what Teams can see so that Team Red (for example) only sees User Stories belonging to the Team Red project
and _does not_ see Requirements from Team Blue ?

## Considered Options

* Using a unique Artifact for Milestones (Program Increments, Iterations)
* Mirror Milestones

## Decision Outcome

Chosen option: "Mirror Milestones", because the other option has an unreasonably high cost (see below).

## Pros and Cons of the Options

### Using a unique Artifact for Milestones

In this solution, there is only one Artifact for Program Increment. Tuleap code must know in which context the Program
Increment is displayed:
- If it's in a Program, Tuleap will show all linked Features
- If it's in Team Red, Tuleap will show all User Stories from Team Red that are children of the Features linked to the
Program Increment
- If it's in Team Blue, Tuleap will show all Requirements (another kind of tracker) from Team Blue that are children of
the Features linked to the Program Increment

We have spiked this solution in the beginning of the epic, but due to the requirement of filtering, we quickly realized
that we would have to add a `project id` parameter to every REST route in Agile Dashboard. Since this "context" project
is never attached to the Program Increment, we cannot find it from just an `artifact id`. This would mean having to
basically rewrite all applications (to pass a `project id`, at the minimum) and their REST routes, to filter
accordingly.

This is made even worse by the fact that Program Management is a plugin, which may or may not be installed.

* Good, because there is no performance cost incurred when creating Program Increments
* Very (very) bad, because we have to change all Agile Dashboard Apps and basically rewrite all their REST routes. On
top of that, at the time of writing we are uncertain about the future of Program Management and whether it is worth
impacting the whole Agile Dashboard for it.
* Bad, because for developers, it becomes difficult to reason about the Program Increment, since you always need a
Project context to know what is linked to it.

### Mirror Milestones

In this solution, there are two kinds of Artifacts: the _Source_ Artifact is the original Artifact created in the
Program project, that will serve as a template for the _Mirror_ Artifacts, which are created by Tuleap in each Team
project. This applies to both Program Increments and Iterations. For example:

```
[Program] Source Program Increment ---- _mirrored_milestone ---> [Red Team] Mirror Program Increment
                                   ---- _mirrored_milestone ---> [Blue Team] Mirror Program Increment
```

Using this strategy, we can filter what is linked in the _Mirror_ Artifacts, while the _Source_ Artifact retains all
original links. This way, the Program Manager in the Program project can see all features, and Team Managers in each
Team can see only what concerns the Team. Tuleap will filter the links.

This solution has the enormous advantage of **not** making us rewrite a significant part of Tuleap's code to enforce the
filtering, but it has the cost of having to keep Mirror artifacts synchronized with the Source.

* Very good, because we don't have to change Agile Dashboard Apps and we don't have to basically rewrite all their REST
routes. We can leave them completely untouched.
* Bad, because we have to maintain some complicated code to:
    - run some checks to ensure Source tracker (for Program Increments) and Mirror trackers (for Mirror Program
    Increments) are compatible: fields definition, semantic configurations, required fields, workflow, etc.
    - re-create an artifact from Source tracker into Mirror trackers
    - synchronize field changes from the Source artifact to the Mirror artifacts
    - synchronize links added/removed from the Source artifact and filter those links for each Mirror artifact
* Bad, because it has a performance cost. Given `<N>` Team projects, when we create a Program Increment or Iteration, we
    have to create `N+1` changesets (it's even `(N × 2) + 1` for Iterations). This performance cost forces us to do
    those operations in an asynchronous way (triggered by Redis queue), which means there is a slight delay between
    creating the Source artifact and the moment all the Mirror artifacts are created and linked.

## Links

* Definition of terms: [glossary][1]
* Program Management [epic][0]

[0]: https://tuleap.net/plugins/tracker/?aid=16683
[1]: <../glossary.md>
