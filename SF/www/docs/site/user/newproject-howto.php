<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"HOWTO Setup a New Project at SourceForge"));
?>

<P><B>SourceForge New Project HOWTO</B>

<P>
Introduction

<P>
Hello new SourceForge user ! I'm writing this HOWTO in an effort to make
it easier to setup a new project at SourceForge. This document is
incomplete, (of course) and will hopefully be added to over time as more
features are added to SourceForge. My intention is for this to be a
step-by-step guide for the initial setup of your new project. First I
will outline what I think is the most common situation new developers
will face ; You have a directory of code, maybe even a current CVS
repository, and you wish to use all the features of SourceForge to
continue developing your project in the public eye, free, and open
sourced, and hopefully to attract some new developers to it, and, of
course, users. So let's get started...

<P>
Steps for setting up a new project

<P>
Here is a brief overview of what is involved, details to follow ;

<P>
1. Register as a new user with SourceForge.
<BR>
2. Register a New Project, logged in as the user you created in step 1.
<BR>
3. Setup your group and project information.
<BR>
4. Create your CVS repository with the latest snapshot of your code.
<BR>
5. Setup your group's web site.
<BR>
6. Setup mailing lists for your project.
<BR>
7. Finally, get used to using CVS for development, and get to work !

<P>
...and some other things that you may need ;

<P>
8. Release a file module so people can see your work !
<BR>
9. Read the SourceForge site documentation and the help message forums.

<P>
Now on to the business...

<P>
1. Register as a new user with SourceForge.

<P>
On the SourceForge homepage, click on the option New User via SSL. This
will allow you to give your details to SourceForge, and it will then
setup a user account for you. To do just about anything involving
interacting with SourceForge you need to have a user account. Once this
is done, you can login as this user anytime using the Login via SSL
option on the SourceForge homepage.

<P>
When you login you are presented with your personal page ; any projects
you are involved in are listed, along with some other info. Along the
side of the page are some options for you, including one which is of
particular interest to us ; Register New Project...

<P>
2. Register a New Project, logged in as the user you created in step 1.

<P>
Clicking on the Register New Project link takes you to a set of pages
which you must simply step through, providing all the information
required. It is very straightforward and well setup, so no help here is
really needed.

<P>
Once complete, it gets sent to the SourceForge staff, and they look over
it to determine if your project would be suited to the goals of
SourceForge. I've yet to hear of a project rejected, so don't worry ; if
its open sourced and free, I think you'll get accepted quickly.

<P>
Now you wait, and hopefully receive a reply within 24 hours. (if it takes
longer, mail them directly - there were some problems with new projects,
but I believe they have been corrected now) If all goes well you will be
informed of your new project being up, and so login via SSL and at the
bottom of your personal page there will be a link to your new project's
group page...

<P>
3. Setup your group and project information.

<P>
Click on that link ! You will now see your group's SourceForge page (this
is different from your web site), and a list of options allowing you to
change things,  basically to administer your project.

<P>
What do you have now ? Well, you have all of the following ;
a website at http://Your_Project_Name.sourceforge.net
a CVS repository at
cvs.Your_Project_Name.sourceforge.net:/cvsroot/Your_Project_Name ,
anonymous FTP at ftp.Your_Project_Name.sourceforge.net,
and access to mailing lists, which we'll use later.

<P>
The first thing to do is to enter a description of your project for your
group page. Click on Project Admin, and then on Edit Group public
Information and Categorization. Insert the required info into the fields.

<P>
Next question ; Do you have any other developers already ? If so, they
must all register as users on SourceForge, and then give you their user
names. You can then add them by user name to your list of developers,
giving them write access to your group's CVS and web site. To do this go
back to Project Admin, and click on Add Group Member. Now type in their
user name, and they will be added.

<P>
The rest of your group page you can explore and see what else is about. I
think  we've done the most important things here. Time to get some code
where everyone can see it...

<P>
4. Create your CVS repository with the latest snapshot of your code.

<P>
For people new to internet development (as I am myself) CVS takes a
little getting used to. For the newbies, here is a brief description of
what it does and how ;

<P>
It keeps a record of every change made to the source code, along with
comments about that change. At any time you can see any previous version
of any file in the repository. It allows multiple people to work on the
same files at the same time, merging the changes as they are "committed",
and alarming the user if two changes conflict (very clearly, I might add)
making sure the difference is resolved by a human being before allowing
the new version of that file to be placed into the repository.

<P>
This is cool. It takes some getting used to however. Basically the
development cycle goes like this ;
<BR>
(1) You import all your code into the CVS, then everyone "checks out" a
working copy of the source tree.
<BR>
(2) Each person works in the comfort of their own computer generated
reality, and when they have a new feature working, they "update" their
local copy to be as much in sync with the current version as possible,
and then they "commit" the files that have changed, back into the
repository.
<BR>
(3) Any problems CVS has with commiting the files will be mentioned, and
you must then go through the problem files and resolve the differences
manually. In the files, the changed section is highlighted with >>>>> and
both versions are shown. Simply delete the old version (or edit to make
it work how its supposed to), and re-commit the file. Once CVS has no
problem, return to step (2), and get back to work !

<P>
So, now to the details of importing your source tree into your CVS
repository at SourceForge. First read the site documentation on CVS. For
more info read a bit of the free 180 page CVS book. Then do the following
;

<P>
Get SSH and CVS for your platform. Under linux using a Bash shell, type
in the following ;

<P>
export CVS_RSH=ssh
exportCVSROOT=Your_User_Name@cvs.Your_Project_Name.sourceforge.net:/cvsroot/Yourct_Name

<P>
The first line tells CVS to use SSH to connect to the repository. This is
for security purposes. The second line tells CVS where to look for its
Repository.

<P>
Now, the most likely situation is that you have some source code in a
directory tree you wish to import into CVS. Go to the top directory you
wish to import, in your source code tree, and type in the following,
filling in the bits mentioned below ;

<P>
cvs import Directory_Name vendor start

<P>
Directory_Name is the name under which the repository will be accessed.
If all goes well it will ask for your SourceForge user password, and then
go on its merry way importing your whole source tree.

<P>
Next, backup your old code base somewhere, because you don't want to work
with it anymore, and checkout a fresh CVS version using ;

<P>
cvs checkout Directory_Name

<P>
This will get you a "working copy" of the code, in CVS form, ready for
you to hack on. You should remember, however, that any changes such as
adding/removing files and directories must be explicitly stated to CVS -
see the above mentioned book for details.

<P>
5. Setup your group's web site.

<P>
I'll assume that you have some sort of web page or site built already
that you wish to put up on SourceForge to give your group a public face.
Login to SourceForge using SSH, for example ;

<P>
ssh -l Your_User_Name Your_Project_Name.sourceforge.net

<P>
you will now be in your home directory. From here all group files are
stored in /home/groups/Your_Project_Name. Change to this directory. All
your web pages are stored in the ht_docs directory. Going in here will
contain index.php, which is a blank page that says you haven't uploaded a
web page yet. Logout, and copy the files for your web page using scp, a
program which comes with ssh. For example ;

<P>
scp Local_File_To_Upload  
Your_User_Name@shell.sourceforge.net:/home/groups/Your_Project_Name/ht_docs

<P>
I suggest you gzip up your site, send it in one go using scp, then login
with ssh and gunzip it.

<P>
So what should be on the web page ? Perhaps you'd like the following, as
well as your project's information ;
(1) A link to the projects group page on SourceForge.
<BR>
(2) Mailing list links (we'll create them in a second)
<BR>
(3) A SourceForge icon and web counter - see the site documentation for
<BR>
this.
(4) A link to the CVS web interface at
http://cvs.sourceforge.net/cgi-bin/cvsweb.cgi/?cvsroot=Your_Project_Name
<BR>
(5) Some info about using CVS specific to your project.
<BR>

<P>
6. Setup mailing lists for your project.

<P>
If your project isn't that active yet (or only has one lonely developer)
your mailing lists will be very quiet, however I still suggest you look
to the future and setup three standard lists for your project ; a devel
list for developers, a users list for users, and an announce list for new
version announcements.

<P>
Do this via your group page using the mailing list admin link. Its quite
simple so you shouldn't have any problems...

<P>
7. Finally, get used to using CVS for development, and get to work !

<P>
I'll give you some quick details of the development cycle to get you
started, but anything you'll have to look up yourself.


<P>
To update your working copy to be in sync with the CVS repository one use
;

<P>
cvs -z3 update -Pd

<P>
(after setting up CVSROOT and CVS_RSH as outline previously)
For commiting the changes to a file ;

<P>
cvs commit -m "comment about changes." filename

<P>
To add a new file to the CVS repository ;

<P>
cvs add filename
cvs commit -m "added filename" filename

<P>
...and now you're ready to go !

<P>
Some other things that you may need ;

<P>
8. Release a file module so people can see your work !

<P>
9. Read the SourceForge site documentation and the help message forums
from the homepage. Recommended.

<?php
$HTML->footer(array());

?>
