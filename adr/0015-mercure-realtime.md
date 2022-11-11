# Replacing NodeJs realtime server with Mercure

* Status: accepted
* Deciders: Florian Caillol, Yannis Rossetto, Manuel Vacelet, Thomas Gerbet
* Date: 29/07/2022

Technical Story: [story #27884](https://tuleap.net/plugins/tracker/?aid=27884)

## Context and Problem Statement

Certain parts of tuleap lose a lot of usability if they are reliant on the user refreshing the web page to keep the information being displayed up to date, and as such require to be updated in real-time to adequately reflect what Tuleap knows.

For example, the Kanban is used by a team of people to report their progress on a project. This makes it so that multiple instances of the same kanban will be displayed at the same time, and each interaction by any user should be displayed to every other user to allow coordination.

This real-time updating is currently being handled by a custom-made NodeJs server that uses [socket.io](http://socket.io).\
Furthermore, the ability to create custom message handling code, including the ability to modify messages in the NodeJs server has made it so that some operations are handled by the PHP backend, and some by the NodeJs server, which while advantageous when you want to quickly make something works, leads to inconsistencies between what the server sends and what the client sees, that can only be understood if you have prior knowledge of realtime, making it harder to maintain.

## Decision Drivers

* Maintainable
* Can be secured to not leak information
* Ease of deployment for end users

## Considered Options

* Rewrite our Nodejs app using socket.io
* Replace NodeJs by [Mercure](http://mercure.rocks)

## Decision Outcome

Replacing NodeJs by [Mercure](http://mercure.rocks) has been chosen, because it meets all the decisions' drivers being both actively developed, secured by an extensive permission system, and easy to deploy, with a docker image available for quick development, and a binary that can be integrated into any Linux server.

As a result :
* New infrastructure needs to be established, to enable a public-facing [Mercure](mercure.rocks) server
* Every plugin that was using NodeJs previously needs to be refactored. That includes both :
	* Kanban
	* TestManagement

We will start by establishing a development infrastructure,  that will then allow us to refactor Kanban, as it is the simplest use case, and then move on to TestManagement.

### Positive Consequences

* Easier to maintain
* Handling lost connections/missed messages by being able to resend them when necessary is included in Mercure.

## Pros and Cons of the Options

### Rewrite our Node.js app using socket.io


* Good, because it has proven that it can be used for our limited use cases that are currently enabled
* Bad, because it leads to bad practices such as those described before
* Bad, because actively maintaining and developing a proper real-time server beyond a simple "It works" can be challenging

### Replace NodeJs with Mercure

* Good, because it forces better practices which will be beneficial in the long term and will make it easier to add real-time functionalities to other plugins
* Good, because it uses Server Side Event(SSE) which are much easier to debug using basic development tools than socket.io
* Bad, because it will require extensive refactoring of the code
* Bad, because it introduces  reliance on external code, and although Mercure is open source, maintaining in case of abandonment by the main dev will be challenging

## Links <!-- optional -->

* [Web ressource] [Mercure](http://mercure.rocks)
* [Web ressource] [socket.io](http://socket.io)
