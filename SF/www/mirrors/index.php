<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"Mirrors of Other Sites"));
?>
<FONT face="arial, helvetica" size="5"><B>Site Mirrors</B></FONT>
<HR NoShade>

<P>SourceForge provides high-bandwidth mirrors for several other
projects. Our mirror server is a Quad XEON 400Mhz, with 2 GB RAM
and 850 GB of formatted storage on 5 Mylex ExtremeRAID controllers. Its
switched 100Mbit connection feeds directly to VA routers and two
DS-3 lines.

<P>Following is a partial mirror list. All mirrors can be found at:
<UL><LI><B><A href="http://download.sourceforge.net/pub/mirrors/">http://download.sourceforge.net/pub/mirrors/</A></B>
(preferred)
<LI><B><A href="ftp://download.sourceforge.net/pub/mirrors/">ftp://download.sourceforge.net/pub/mirrors/</A></B>
</UL>

<P>To report problems with these mirrors, or to suggest another mirror,
send email to <A href="mailto:dtype@sourceforge.net">dtype@sourceforge.net</A>.

<HR>

<P><B><A href="http://download.sourceforge.net/mirrors/CPAN/">CPAN</A></B> -
CPAN is the Comprehensive Perl Archive Network. Here you will find All
Things Perl. <I>(We are a primary CPAN mirror.)</I>

<P><B><A href="http://download.sourceforge.net/mirrors/debian/">Debian GNU/Linux</A></B> -
Debian is the largest GNU/Linux distribution completely maintained by a base of volunteers.
More than 500 developers currently work to make Debian the most complete distribution available
today. <I>(We are a master push mirror for Debian.)</I>

<P><?php html_image('others/gnome1.png',array(align=>'right')); ?>
<B><A href="http://download.sourceforge.net/mirrors/gnome/">Gnome</A></B> - GNOME 
is the GNU Network Object Model Environment. The GNOME project intends 
to build a complete, easy-to-use desktop
environment for the user, and a powerful application framework 
for the software developer. <I>(We are a primary Gnome mirror.)</I>

<P><?php html_image('others/kde-logotp3.png',array(align=>'right')); ?>
<B><A href="http://download.sourceforge.net/mirrors/kde/">KDE</A></B> -
KDE is a powerful graphical desktop environment for Unix workstations. It combines
ease of use, contemporary functionality and outstanding graphical design with the
technological superiority of the Unix operating system.  
<I>(We are a primary KDE mirror.)</I>

<P><B><A href="http://download.sourceforge.net/mirrors/kernel.org/">kernel.org</A></B> -
The Linux Kernel Archives is the primary site for the Linux kernel source.
<I>(We are in the Linux Kernel Archive Mirror System for www/ftp.us.kernel.org.)</I>

<P><B><A href="http://download.sourceforge.net/mirrors/linuxppc/">Linux/PPC</A></B> -
Linux/PPC runs natively on PCI-based Apple PowerMacs, many IBM &amp; Motorola PReP and CHRP
workstations, Amiga Power-UP systems and several embedded platforms including the Motorola MBX and
RPX. <I>(We are a primary Linux/PPC mirror.)</I>

<P><B><A href="http://download.sourceforge.net/mirrors/metalab/">metalab.unc.edu</A></B> -
The /pub/Linux directory for metalab (formerly sunsite.unc.edu) has long been a first 
rate collection of Linux-related software. SourceForge now mirrors this collection in
its entirety. 

<P><B><A href="http://download.sourceforge.net/mirrors/mozilla/">mozilla.org</A></B> -
Mozilla is an open-source web browser, designed for standards compliance, performance 
and portability. <I>(We are a primary mozilla mirror.)</I>

<P><B><A href="http://download.sourceforge.net/mirrors/NetBSD/">NetBSD</A></B> -
NetBSD is a free, highly portable UNIX-like operating system available for many platforms, from
64bit alpha servers to handheld devices. Its clean design and advanced features make it
excellent in both production and research environments.
<I>(We are a primary NetBSD mirror.)</I>

<P><B><A href="http://download.sourceforge.net/mirrors/OpenBSD/">OpenBSD</A></B> -
The OpenBSD project produces a FREE, multi-platform 4.4BSD-based UNIX-like operating 
system. Our efforts emphasize portability, standardization, correctness, proactive 
security and integrated cryptography.
<I>(We are a primary OpenBSD mirror.)</I>

<P><B><A href="http://download.sourceforge.net/mirrors/redhat/">Red Hat Linux</A></B> -
Red Hat Linux is a powerful, extremely stable,
next-generation computer operating system that provides a
high performance computing environment for both server
and desktop PCs.
<I>(We are a primary Red Hat mirror.)</I>

<P><B><A href="http://download.sourceforge.net/mirrors/storm/">Storm Linux</A></B> -
Storm Linux 2000 is based on the award winning Debian GNU/Linux. We've
enhanced Debian's robust, open source, distribution by making it easier to install,
configure, administer, and update.
<I>(We are mirror4.stormix.com.)</I>

<P><?php html_image('others/suse_g_sm.png',array(align=>'right')); ?>
<B><A href="http://download.sourceforge.net/pub/mirrors/suse/">SuSE Linux</A></B> -
SuSE Linux provides a comprehensive set of software, and features the YaST2 setup
and configuration tool with automatic hardware detection for PCI components and a
menu-driven graphical interface. For the experienced SuSE Linux user, the familiar
YaST1, with all its features, continues to be available for updates and system
administration.
<I>(We are a primary SuSE Linux mirror and US distribution point.)</I>

<P><B><A href="http://download.sourceforge.net/mirrors/XFree86/">XFree86</A></B> -
XFree86 is a freely redistributable implementation of the X Window
System that runs on UNIX(R) and UNIX-like operating systems (and OS/2). 
The XFree86 Project has traditionally focused on Intel x86-based
platforms (which is where the `86' in our name comes from), 
but our current release also supports other platforms.
<I>(We are a primary XFree86 mirror.)</I>

<P><B><A href="http://download.sourceforge.net/mirrors/yellowdog/">Yellow Dog Linux</A></B> -
Yellow Dog Linux is the most complete Linux distribution for PowerPC.                                         
This operating system takes full advantage of the most current, stable, and
secure Linux kernel and libraries, and best of all, some of the fastest hardware
on the planet.
<I>(We are in the ftp.yellowdoglinux.com DNS rotation.)</i>

<?php
$HTML->footer(array());

?>
