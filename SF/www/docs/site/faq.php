<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"Frequently Asked Questions (FAQ)"));
?>

<P><B>SourceForge Frequently Asked Questions (FAQ)</B>
<BR>v.1.1.0

<UL>
<LI><A href="#what-main">What is SourceForge?</A>
	<UL>
	<LI><A href="#what-os">What is Open Source software?</A>
	</UL>
<LI><A href="#whodev-main">Who develops SourceForge?</A>
	<UL>
	<LI><A href="#whodev-why">Why did you create SourceForge?</A>
	<LI><A href="#whodev-pays">Who pays for SourceForge?</A>
	<LI><A href="#whodev-mot">What is the motivation to pay for something like this?</A>
	<LI><A href="#whodev-catch">This seems like an awful lot of free stuff. What's the catch?</A>
	<LI><A href="#whodev-help">That's cool! I'd really like to help. Can I?</A>
	</UL>
<LI><A href="#whohost-main">Who can host with SourceForge?</A>
	<UL>
	<LI><A href="#whohost-web">What if I don't have software, but wanted you to host my web site?</A>
	<LI><A href="#whohost-rest">Are there any restrictions on the types of software I can host here?</A>
	<LI><A href="#whohost-com">Can I host commercial software on SourceForge?</A>
	<LI><A href="#whohost-owns">Who owns the source code on SourceForge?</A>
	<LI><A href="#whohost-stop">What if I host with SourceForge, then decide to stop?</A>
	</UL>
<LI><A href="#whyhost-main">Why should I host my software with SourceForge?</A>
	<UL>
	<LI><A href="#whyhost-secure">Is SourceForge secure?</A>
	<LI><A href="#whyhost-backup">What is your backup strategy?</A>
	<LI><A href="#whyhost-all">Do I have to use all of your services? Can I just use mailing lists, or bug tracking?</A>
	<LI><A href="#whyhost-root">Won't I lose a lot of control if I don't have root access?</A>
	<LI><A href="#whyhost-cvs">I already have a cvs tree. Can you import it?</A>
	<LI><A href="#whyhost-files">I have a lot of file releases already. Can I make them available on the main file server?</A>
	</UL>
<LI><A href="#big-main">So how big is all this, really?</A>
	<UL>
	<LI><A href="#big-fad">Isn't Open Source a fad? Can it really work?</A>
	<LI><A href="#big-cap">How much capacity does SourceForge have right now?</A>
	<LI><A href="#big-scale">Wow. That's a lot. Can it scale?</A>
	<LI><A href="#big-mirror">Are you going to mirror SourceForge? (and can I pay for it?)</A>
	<LI><A href="#big-ready">I thought Linux wasn't ready for the enterprise. What was your setup experience like?</A>
	<LI><A href="#big-wait">What improvements in Linux/Open Source software are you still waiting for? </A>
	<LI><A href="#big-future">What is the future of SourceForge?</A>
	</UL>
<LI><A href="#whowrote">Who wrote this FAQ? Are you available?</A>
</UL>

<HR noshade>

<P><A name="what-main">&nbsp;</A><B>What is SourceForge?</B>
<P>SourceForge is a free hosting service for <A href="http://www.opensource.org">Open Source</A> 
developers which offers, among other things, a CVS repository, mailing lists,
bug tracking, message forums, task management software, web site hosting,
permanent file archival, full backups, and total web-based administration.
A more <A href="/docs/site/services.php">complete description of services</A> is
available. 

<P><A name="what-os">&nbsp;</A><B>What is Open Source software?</B>
<P>In short, Open Source software is software with source code made available to the public,
with no fees or royalties for use or distribution. The official definition, as well
as the rationale and history behind open source software, can be found
at <A href="http://www.opensource.org">The Open Source Initiative</A>'s web site.

<P><A name="whodev-main">&nbsp;</A><B>Who develops SourceForge?</B>
<P>The <A href="/staff.php">SourceForge development team</A>, Tim, Drew, and Uriah,
develop most of what you see on SourceForge now. The site is additionally developed
and supported by Dan Bressler, our product manager, Tony Guntharp, and Quentin, who
manages user requests and several other issues. We of course owe a lot of credit
to the <A href="/docs/site/software.php">software</A> that made our work a lot easier,
and to the <A href="/thanks.php">people and organizations</A> that donated time
and hardware to make SourceForge possible. 
<P>SourceForge started out as a small idea that just refused to stop growing. 
Many people have been incredibly helpful along the way. We would like to thank
the entire open source community for the support that they are known for.

<P><A name="whodev-why">&nbsp;</A><B>Why did you create SourceForge?</B>
<P>Strangely, a system this big wasn't our original intention. We really had no idea
that there would be such a reception for this kind of innovation in hosting
open source software.
<P>As open source developers ourselves, we have run into the kinds of obstacles
that still plague many would-be developers. It was our intent to remove many
of those obstacles and let developers focus on software development.
(An odd concept, but easier to get used to than you'd think.) 
<P>A suite of tools isn't enough, though. In the end, you need the hardware
power for the whole setup, which might lead to your next question:

<P><A name="whodev-pays">&nbsp;</A><B>Who pays for SourceForge?</B>
<P><A href="http://valinux.com">VA Linux Systems</A> has supported this project
from the beginning. Some of us were employed by VA before the conception
of SourceForge (although none had ever worked in the same department), others
were hired specifically for this project.
<P>The <A href="/docs/site/hardware.php">hardware behind this site</A> is truly
impressive, and has proven to us VA's dedication to this project and to Open Source
development.

<P><A name="whodev-mot">&nbsp;</A><B>What is the motivation to pay for something like this?</B>
<P>Stop looking for ulterior motives or fantasies about world domination.
There are some good business reasons why a Linux company would
benefit from a site such as this.
<P>VA Linux Systems thrives upon the success of Open Source software in
general. They sell Linux systems, installed with a myriad of Open Source
tools and applications, to clients that require enterprise-ready
components and software. They also sell support for these systems.
If the selection and quality of Open Source
software improves, VA can offer its customers more competitive solutions.
<P>On a more personal note, VA is not a behemoth with no sense
of the community which made it grow. I joined VA because they would allow
me to continue to spend significant amounts of my time in Open Source development.
They have never taken that freedom from me, and in fact have encouraged
employees to take part in community endeavors. This site is the real 
thing. -drew

<P><A name="whodev-catch">&nbsp;</A><B>This seems like an awful lot of free stuff. What's the catch?</B>
<P>There's no catch.
<P>I'm an Open Source developer. I work on a project to aid other Open Source developers.
So do many other developers. An Open Source friendly company pays for it, and we thank them a lot.

<P><A name="whodev-help">&nbsp;</A><B>That's cool! I'd really like to help. Can I?</B>
<P>Of course.
<P>We're working on a lot of ways that we can utilize the help of the
rest of the community.
<P>We have already released many of the SourceForge web-based tools as
Open Source projects in SourceForge. (That still confuses me, but
somehow the logic works.) This means that we can take on developers on 
these projects, and as the tools improve, the new versions will automatically
be available to other SourceForge project developers.
<P>There are a number of other ways that users of SourceForge to help.
We expect to eventually have some sort of news system that will require maintenance,
we could definitely use help answering emails, and we could always
use help with site design. (Don't laugh. We're software developers, not artists.)
<P>Everyone can help by answering the site surveys that we will
occasionally post. Answers to these questions will directly influence
the direction of our development.

<P><A name="whohost-main">&nbsp;</A><B>Who can host with SourceForge?</B>
<P>We're trying to keep it simple, and chose to rely on the work of a
very talented group, the <A href="http://www.opensource.org">Open Source Initiative</A>.
If your software utilizes one of the OSI's 
<A href="http://www.opensource.org/licenses/">approved licenses</A>, we'd
love to offer you hosting at SourceForge. Software that falls under
other licenses will require further scrutiny, but is not altogether ruled out.
In the end, we're looking to further Open Source software development,
and will approve projects accordingly.

<P><A name="whohost-web">&nbsp;</A><B>What if I don't have software, but wanted you to host my web site?</B>
<P>If you're site is oriented towards the Open Source community, we'd
probably be happy to offer you hosting. Please be careful with this one, though.
We're not here to host personal homepages, or your Southpark picture
archive (despite how much we all love Southpark). There are plenty of
free site hosting services for these sites. Our goal is the advancement
of Open Source, and if your web site does that, we'd love to have you.

<P><A name="whohost-rest">&nbsp;</A><B>Are there any restrictions on the types of software I can host here?</B>
<P>For several reasons, we cannot host
material/software of a pornographic nature. (As nice as a local connection
to your porn archive might be to us, we just can't do it.) 
<P>Because of recent changes in US export regulations, we now *can* host
strong encryption code, as long as the source code is always available.
<P>There is a more complete definition
of these restrictions in the terms of service agreement, presented 
during project registration.
<P>Cut us some slack on this one. We're trying to keep ourselves and the
company that provided all of this hardware and bandwidth out of trouble.
We're not here to start a political discussion.

<P><A name="whohost-com">&nbsp;</A><B>Can I host commercial software on SourceForge?</B>
<P>Maybe.
<P>If your commercially developed software is Open Source, then yes.
<P>If your commercially developed software is not yet Open Source, but will be later, then probably yes.
<P>If your commercially developed software is not Open Source, but may help
to advance other Open Source software, then maybe.
<P>If you just like our tools so much that you want to use them, then
go ahead. We've released them as Open Source software. If this
prospect overwhelms you, then contact VA Linux Professional
Services, they'll be glad to help you. (Shameless plug for
our sponsors.)

<P><A name="whohost-owns">&nbsp;</A><B>Who owns the source code on SourceForge?</B>
<P>The individual authors hold the copyright on their own software. Because
of the nature of Open Source licenses, we are allowed to give people access
to that software; however, all license disputes and issues are the responsibilities
of the individual authors.
<P>There is often a misconception that Open Source software does not actually
have a license holder, because of its free nature. This is not the case, however,
and software hosted on SourceForge is responsible for its own licensing.

<P><A name="whohost-stop">&nbsp;</A><B>What if I host with SourceForge, then decide to stop?</B>
<P>We would of course like to solve any problems you were having before it came
to this, but in the end, you are free to leave at anytime. After all, we're not
the copyright holders.
<P>Because your software was/is Open Source, however, we will probably still
have the right to continue to make existing versions of your software available
to the public. If we didn't, someone else probably would anyway.

<P><A name="whyhost-main">&nbsp;</A><B>Why should I host my software with SourceForge?</B>
<P>...mumble...free...mumble...gift horse....
<P>But seriously, we're not trying to force anything on anybody here. We would
love people to host with SourceForge because we are a superior hosting service.
In the spirit of Open Source, we want any popularity we gain to be merit-based.
<P>As we grow, we will also be able to provide you with traffic to your project.
There is a lot of benefit to giving end-users a window into your development
environment. There will be more peer review of your source, and potential developers
will be wandering by.

<P><A name="whyhost-secure">&nbsp;</A><B>Is SourceForge secure?</B>
<P>Yes, we have taken extreme caution in the methods we use for SourceForge
services. No system is uncrackable, but we continuously monitor the software
we use for bugs and security holes, and audit our own software regularly.
<P>Encryption is available and enforced on all parts of the SourceForge site
except mailing lists. (These passwords should be different than your site
passwords. We are in the process of modifying GNU Mailman to use our own 
secure authentication system.)
<P>We give SSH accounts rather than telnet, SCP rather than ftp, and
CVS/SSH instead of pserver. Site logins are via SSL and site passwords
are never stored nor communicated in plaintext. Because it is possible
to change your site password by confirming your email address (via
a partial MD5 hash sent to your registered address), we recommend that
you also keep your own mail account secure.

<P><A name="whyhost-backup">&nbsp;</A><B>What is your backup strategy?</B>
<P>We perform a full backup of all site and project data daily, and rotate
backups off-site weekly through a large company that specializes in
storing backup tapes. 
We also have access to a fireproof safe for storage of
daily tapes.
<P>The exception to this rotation is the mega-file-server, which cannot be fully
backed up (when full) by less than 34 25GB AIT tapes (our medium of
choice). It gets a full backup once per month and incrementals otherwise. 

<P><A name="whyhost-all">&nbsp;</A><B>Do I have to use all of your services? 
Can I just use mailing lists, or bug tracking?</B>
<P>You can use whatever you want. If you don't need all of the services now,
don't use them. They are there when you need them.

<P><A name="whyhost-root">&nbsp;</A><B>Won't I lose a lot of control if I don't have root access?</B>
<P>Yes and no.
<P>The tradeoff is the security and stability of a professionally managed server.
<P>Once initial setup is through, there really isn't much need for root
access except for security and maintenance upgrades, which we are taking
care of. We would like to implement all other functions for which you would
normally require root access via a web interface.
<P>Obviously we can't give root access to everyone, or your own security
is at the disposal of any other project admin. We're trying to find a happy
medium. In general we'll try to give as much access as we can without damaging
the security of other projects within SourceForge.
<P>In the end, we're here to serve you. VA Linux Systems now pays our salary to
offer you these services. We're a pretty responsive bunch. We'd like to work
through any issues you have, just let us know what we need to do. We hope
that you'll find our web administration tools powerful and easy to use. We're 
constantly continuing to develop and improve these tools.
If there is some feature we can add that you think we've forgotten, 
please let us know.

<P><A name="whyhost-cvs">&nbsp;</A><B>I already have a cvs tree. Can you import it?</B>
<P>Yes. Register a new project normally and let us know you need to import a
CVS tree. We'll need a tar/gzip of your entire document root, including CVSROOT
directory. This will preserve your revision history.

<P><A name="whyhost-files">&nbsp;</A><B>I have a lot of file releases already. 
Can I make them available on the main file server?</B>
<P>Yes, when releasing new files via the project admin interface, there
is an option to set the release date of each file. You may back-populate
your release history in this manner. That way all previous revisions of your
software are available on the main file server.

<P><A name="big-main">&nbsp;</A><B>So how big is all this, really?</B>
<P>We are all of course biased, but it is this writer's opinion that this is one
of the best opportunities for Open Source developers in history.

<P><A name="big-fad">&nbsp;</A><B>Isn't Open Source a fad? Can it really work?</B>
<P>Ack! 
<P>This site's developers and the company behind it believe deeply in the concepts
of Open Source software.
<P>Open Source is not a fad. Open Source can and does work. This site is proof of
that. We would not have been able to offer many of our functions without modifications
to many programs we use. Their open source let us make these modifications (which
were sent back to the original authors for inclusion in the next versions of their
software).
<P>&lt;RANT&gt;I can't believe that after BIND, Apache, Samba, PERL, Linux, and the
GNU toolset, you can still ask that question.&lt;/RANT&gt;

<P><A name="big-cap">&nbsp;</A><B>How much capacity does SourceForge have right now?</B>
<P>A quick look at our <A href="/docs/site/hardware.php">hardware list</A> will partially
answer that question.
<P>We've performed many real-world benchmarks of the various parts of our systems.
We are capable of handling many projects now, and will scale our systems
to meet demand. Nobody likes a slow server, especially us.
<P>If you want some quick stats on our current systems, here you go:
<UL>
<LI>At 100% usage, our main web servers can handle approximately 8.6 million PHP pageviews
daily. (Including all of the site authentication necessary for each view.)
<LI>The project web server can handle about 12 million pageviews daily with simple PHP
pages, and some unknown (think large numbers) millions of static pages.
<LI>We can't properly benchmark our main file server, because we keep saturating its
current 100Mbit connection. We've managed to get 93.8Mbit with 5000 concurrent HTTP
connections with it though (using only one of its four processors). If we start
approaching our facility bandwidth (a couple of DS-3's and a T-1 right now), then we'll start
looking at gigabit and more digital drops. (Don't tell the CFO.)
<LI>The 1.3 terabytes of storage in the room gets very loud.
<LI>We blew many circuit-breakers when we were building this thing. Dedicated
220V/30A power circuits solved this problem.
<LI>Our UPS weighs more than our entire team.
<LI>VA systems rock. (Another shameless sponsor plug)
</UL>

<P><A name="big-scale">&nbsp;</A><B>Wow. That's a lot. Can it scale?</B>
<P>Yes, and don't threaten us or we'll do it.

<P><A name="big-mirror">&nbsp;</A><B>Are you going to mirror SourceForge? (and can I pay for it?)</B>
<P>We will probably be mirroring the main file server sometime in the near
future. The real problem is the databases behind the main website and synchronization
of all the other servers in real-time.
<P>For now, we're content to have good backups, and spare hardware to put
in place if something should fail. 
<P>We do take reliability seriously, and a non-west-coast mirror does seem like
a good idea. In lieu of a realtime mirror, we're considering setting up a 
failover mirror that would lag a few minutes behind the live site.
We're pursuing our options here and will keep you posted.
<P>As for the second question... VA's CFO says, yes, you may pay for it.

<P><A name="big-ready">&nbsp;</A><B>I thought Linux wasn't ready for the 
enterprise. What was your setup experience like?</B>
<P>We're working on a paper detailing our experience setting up this system.
We expect to publish it soon.
<P>For those of you that can't wait, it can be summarized, "Yay Open Source."

<P><A name="big-wait">&nbsp;</A><B>What improvements in Linux/Open Source software are you still waiting for? </B>
<P>We're especially anxious to see shared disk arrays over fibre channel,
32-bit UIDs (should be fixed with a new file system),
better scheduling (apparent in the development Linux kernels),
and support for my Philips PC Camera.

<P><A name="big-future">&nbsp;</A><B>What is the future of SourceForge?</B>
<P>It is looking good, my friend.
<P>VA is committed, we're committed, and we've already got ideas that will
continue to drive us for months.
<P>The rest is up to you. Help us to help you, and we can all help to advance
Open Source to new heights.

<P><A name="whowrote">&nbsp;</A><B>Who wrote this FAQ? Are you available?</B>
<P>This FAQ was written by <A href="mailto:dtype@valinux.com">Drew Streib</A> (dtype),
although I'm sure it will be modified by many people in the future, especially
after they see what I've written.
<P>Yes.

<?php
$HTML->footer(array());

?>
