Tuleap Agile Guinea Pig generator
=================================

Generate a new Tuleap project with an agile template
and load a couple of data/users to start to play

Usage:

    $> /usr/share/tuleap/src/utils/php-launcher.sh seed.php GuineaPig

Tutorial
========

First, open %url%, click login as log as "richard_cover" with "welcome0" as password.

You land on your personal page, made of widgets. All those widgets are configurable,
you can move them around, remove those you don't need, add some others (see customize button).

On top left widget, you have the list of your projects, click on "Guinea Pig" to
enters one.

Guinea Pig project
------------------

A project expose a list of services, those services are on the left part of the
interface (the sidebar). This sidebar can be collapsed to save space with the
little arrow at the bottom.

For this tutorial, we will do some actions on 2 of those services:
- Agile Dashboard
- Git

We will pick up a task on the agile dashboard and commit some stuff in git

Agile Dashboard
---------------

Open Agile Dashboard

You can see that there is a Sprint on going (sprint 1) with 2 elements open.
On the left, you see the release the sprint belongs to.

On the top, you can access releases and sprints that were done and planned (What's next)

Click on "Cardwall" for Sprint 1

On the cardwall there is a story and a task that are waiting to be done.

Drag the "Add Readme" task from the "to be done" column and drop it into the
"On Going" column.
Click on the small dash (-) next to "Assigned to" and assign the task to yourself.

Now we will push some code in git for this task

Git
---

Go on git service. There is nothing yet so create a new repository by entering
"gpig" in the text field and click create.

The creation of the repository takes usually 1 or 2 minutes, wait for it and reload the page

When the repository is created you can use it, let's clone on your workstation:

  $> git clone http://URL/git/gpig8/gpig.git

Now create a new file "README" with some content into "gpig" directory and commit

  $> cd gpig
  $> $EDITOR README.txt
  $> git add README
  $> git commit -m "task #1 Add readme"
  $> git push

Please note the commit line, the reference to the task from the agile dashboard

Now go back on the web site, on the git repository viewer, you can see the commit
you just pushed.

Click on the commit message, you access to all the details of the commit. Here
Tuleap recognized things for you, the "task #1" was detected as pattern to a
element in your project and automatically turned in to a link.

Click on the link, you end-up on the task artifact. Change the status to "Done"

Note: you can see the backlink to the git commit.


Agile dashboard 2nd
-------------------

Go back on the Cardwall for Release 1.0 > Sprint 1

You can see that the card is now in "Done" column.

Congratulation you completed a first round of contribution !
