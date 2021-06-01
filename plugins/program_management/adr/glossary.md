# Definition of terms used in Program Management plugin

* Date: 2021-04-20

Technical epic: [epic #16683](https://tuleap.net/plugins/tracker/?aid=16683): Program Management

## Context and Problem Statement

During the development of the Program Management plugin, we used different terms which are not understandable by everyone.

This ADR aims to explain each term to have common knowledge. These terms are used in front end and back end of the plugin.

## Glossary

  * Feature: Artifacts that can be planned in `Program Increment`, respecting the `Plan`. That can be an Epic ...
  * Iteration: An Artifact in the `Program` project that represents a subdivision of a `Program Increment`. `Program Increments` are split into `Iterations` and `User Stories` in the Program Increment's backlog are then planned in Iterations by the `Team` members.
  * Mirrored Iteration: The duplication (mirror) of a `Iteration` in `Team` project. That can be a Sprint, ...
  * Mirrored Milestone: A generic term for Mirrored Program Increment and Mirrored Iteration.
  * Mirrored Program Increment: The duplication (mirror) of a `Program Increment` in `Team` project. That can be a Release, ...
  * Plan: Different tracker that can be used to plan in a `Program Increment`. That can be Feature Tracker, Bug Tracker, ...
  * Program: The project where `Program Increment` are created. The `Program` project will aggregate `Team` project.
  * Program Backlog: The Program Backlog will display the `Features` that'll need to be planed during PI planning.
    The `Features` will be split in `User Stories`, and `User Stories` will be planned in `Release` and `Sprint` of `Team` projects.
  * Sprint: Sub-milestone in `Team` project where `User Stories` are planned. That can be a week, iteration...
  * Team: Project linked to `Program` project. The `Team` project contains `User Stories`. `User stories` can be local or can be inherited from `Feature`.
  * TimeBox: A generic term to describe both `Program Increment` and `Iteration`.
  * User Story: Artifact in `Team` project linked as child to `Feature`. They will be planned automatically during the PI planning, the users will plan `Feature` and `User Stories` linked to `Feature` will be planned by inheritance. That can be an Activity, Request, Bug, ...

  You can have more details of each term, at this [glossary][0].

## Keep it in mind

As it's an implementation of SAFe, we use the same terminology as the framework.
Program management is a complex plugin, so we don't want to complicate it further with terms we don't understand.

Using framework terms simplifies development and lets you know which object you are manipulating.
But the object you are manipulating might not contain exactly what it points to (for example, a "Bug" might be in the object named "User Story ").

[0]: https://www.scaledagileframework.com/glossary/
