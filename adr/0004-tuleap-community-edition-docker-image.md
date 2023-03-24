# Tuleap Community Edition docker image

* Status: accepted
* Deciders: Manuel VACELET, Thomas GERBET
* Date: 2021-06-17

Technical Story: [docker: replace enalean/tuleap-aio by tuleap/tuleap-community-edition](https://tuleap.net/plugins/tracker/?aid=21334)

## Context and Problem Statement

Tuleap Community Edition comes from two different problems:
* First, with the old image (enalean/tuleap-aio) we reached a limit that was hard to workaround. More and more features
  were dependent on backend workers. Backend workers require redis. We don't want to add redis to the image as it was
  already a bad idea to embed mysql.
* Second, we were needing a more straightforward way to ship Tuleap to most users. As things turn out around RHEL/CentOS
  the dependency on CentOS to run a Tuleap will soon be a problem. Having an alternative way to install and run Tuleap
  elsewhere than CentOS is a need.

Building the Tuleap Community Image is not simple because building Tuleap is not simple. There are dependencies outside
the sources (forgeupgrade, cvs, sha1collisiondetector, mediawiki) that must be bundled. Therefore, creating the TCE
image need to take this into account.

## Considered Options

* [option 1] Build the image directly from the sources
* [option 2] Build the image out of the RPM repository

## Decision Outcome

Chosen option: "[option 2] Build the image out of the RPM repository", because it's the quick win that avoid having to
redesign our entire delivery pipeline.

The main risk of option 2 is that the version of the packages that goes into the docker image are not the same than the
one built in the pipeline. However:
* The occurrence is likely to be rare
* The consequence are low (as we are on rolling release, the exact version doesn't matter much)
* It's already a big step forward vs the old enalean/tuleap-aio image that was built once a day.

## Pros and Cons of the Options <!-- optional -->

### [option 1]  Build the image directly from the sources

The pipeline builds the RPMs, the RPMs are used to build the docker image directly (without intermediate step of publishing
the RPMs to their official repo)

* Good, because it's completely end to end. What goes to the image is what was built in the pipeline
* Good, because it's the expected way for this to be done
* Bad, because it's utterly complex to do because, in addition to the Tuleap packages, there a dependencies (forgeupgrade,
  mediawiki, etc) that are external. The RPM delivery pipeline takes this into account to provide a yum repository with
  tuleap related packages and deps. In order to achieve this strategy, we should rebuild what's already done for Yum
  in the docker image.

### [option 2]

Build of the Tuleap Community Edition image comes after the build of the Yum repository and is based on it (i.e., as a regular install).

* Good, it's easy, it's the standard process
* Bad, there is a risk of un alignement between the pipeline (that builds a given version of Tuleap), and the yum repository that might
  contain a slightly different version of Tuleap.
