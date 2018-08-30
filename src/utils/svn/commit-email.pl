#!/usr/bin/perl

# ====================================================================
# commit-email.pl: send a commit email for commit REVISION in
# repository REPOS to some email addresses.
#
# For usage, see the usage subroutine or run the script with no
# command line arguments.
#
# Copyright Enalean (c) 2015-2018. All rights reserved.
#
# $HeadURL: https://svn.collab.net/repos/svn/trunk/tools/hook-scripts/commit-email.pl.in $
# $LastChangedDate: 2012-03-23 08:59:24 +0000 (Fri, 23 Mar 2012) $
# $LastChangedBy: hosniah $
# $LastChangedRevision: 21391 $
#
# Heavily modified by Laurent Julliard for the Codendi project at Xerox
# Copyright (c) Xerox Corporation, Codendi Team, 2004-2009. All Rights Reserved
#
# ====================================================================
# Copyright (c) 2000-2004 CollabNet.  All rights reserved.
#
# This software is licensed as described in the file COPYING, which
# you should have received as part of this distribution.  The terms
# are also available at http://subversion.tigris.org/license-1.html.
# If newer versions of this license are posted there, you may use a
# newer version instead, at your option.
#
# This software consists of voluntary contributions made by many
# individuals.  For exact contribution history, see the revision
# history and logs, available at http://subversion.tigris.org/.
# ====================================================================

# The warning switch is set here and not in the shebang line above
# with /usr/bin/env because env will try to find the binary named
# 'perl -w', which won't work.
#BEGIN
#  {
#    $^W = 1;
#  }

#Codendi is full utf-8
$ENV{LANG} = 'en_US.UTF-8';

#use strict;
use Carp;
use Time::Local;
use File::Basename;
use Encode;

######################################################################
# Configuration section.

# debug output flag
my $debug = 0;

# Sendmail path.
my $sendmail = "/usr/sbin/sendmail";

# Svnlook path.
my $svnlook = "/usr/bin/svnlook";

# By default, when a file is deleted from the repository, svnlook diff
# prints the entire contents of the file.  If you want to save space
# in the log and email messages by not printing the file, then set
# $no_diff_deleted to 1.
my $no_diff_deleted = 0;

# Since the path to svnlook depends upon the local installation
# preferences, check that the required programs exist to insure that
# the administrator has set up the script properly.
{
  my $ok = 1;
  foreach my $program ($sendmail, $svnlook)
    {
      if (-e $program)
        {
          unless (-x $program)
            {
              warn "$0: required program `$program' is not executable, ",
                   "edit $0.\n";
              $ok = 0;
            }
        }
      else
        {
          warn "$0: required program `$program' does not exist, edit $0.\n";
          $ok = 0;
        }
    }
  exit 1 unless $ok;
}

######################################################################
# Initial setup/command-line handling.

# Each value in this array holds a hash reference which contains the
# associated email information for one project.  Start with an
# implicit rule that matches all paths.
my @project_settings_list = (&new_project);

# Process the command line arguments till there are none left.  The
# first two arguments that are not used by a command line option are
# the repository path and the revision number.
my $repos;
local $rev;

# Use the reference to the first project to populate.
my $current_project = $project_settings_list[0];

# This hash matches the command line option to the hash key in the
# project.  If a key exists but has a false value (''), then the
# command line option is allowed but requires special handling.
my %opt_to_hash_key = ('--from' => 'from_address',
		       '-h'     => 'hostname',
                       '-l'     => 'log_file',
                       '-m'     => '',
                       '-r'     => 'reply_to',
                       '-s'     => 'subject_prefix');

while (@ARGV)
  {
    my $arg = shift @ARGV;
    if ($arg =~ /^-/)
      {
        my $hash_key = $opt_to_hash_key{$arg};
        unless (defined $hash_key)
          {
            die "$0: command line option `$arg' is not recognized.\n";
          }

        unless (@ARGV)
          {
            die "$0: command line option `$arg' is missing a value.\n";
          }
        my $value = shift @ARGV;

        if ($hash_key)
          {
            $current_project->{$hash_key} = $value;
          }
        else
          {
            # Here handle -m.
            unless ($arg eq '-m')
              {
                die "$0: internal error: should only handle -m here.\n";
              }
            $current_project                = &new_project;
            $current_project->{match_regex} = $value;
            push(@project_settings_list, $current_project);
          }
      }
    elsif ($arg =~ /^-/)
      {
        die "$0: command line option `$arg' is not recognized.\n";
      }
    else
      {
        if (! defined $repos)
          {
            $repos = $arg;
	    # make sure we normalize the path to the repository. There should be
	    # no slash at the end
	    $repos =~ s|/*\s*$||;
          }
        elsif (! defined $rev)
          {
            $rev = $arg;
          }
        else
          {
            push(@{$current_project->{email_addresses}}, $arg);
          }
      }
  }

# If the revision number is undefined, then there were not enough
# command line arguments.
&usage("$0: too few arguments.") unless defined $rev;

# Check the validity of the command line arguments.  Check that the
# revision is an integer greater than 0 and that the repository
# directory exists.
unless ($rev =~ /^\d+/ and $rev > 0)
  {
    &usage("$0: revision number `$rev' must be an integer > 0.");
  }
unless (-e $repos)
  {
    &usage("$0: repos directory `$repos' does not exist.");
  }
unless (-d _)
  {
    &usage("$0: repos directory `$repos' is not a directory.");
  }

# Check that all of the regular expressions can be compiled and
# compile them.
{
  my $ok = 1;
  for (my $i=0; $i<@project_settings_list; ++$i)
    {
      my $match_regex = $project_settings_list[$i]->{match_regex};

      # To help users that automatically write regular expressions
      # that match the root directory using ^/, remove the / character
      # because subversion paths, while they start at the root level,
      # do not begin with a /.
      $match_regex =~ s#^\^/#^#;

      my $match_re;
      eval { $match_re = qr/$match_regex/ };
      if ($@)
        {
          warn "$0: -m regex #$i `$match_regex' does not compile:\n$@\n";
          $ok = 0;
          next;
        }
      $project_settings_list[$i]->{match_re} = $match_re;
    }
  exit 1 unless $ok;
}

#####################################################################
#
# Connect to Codendi database and include all files needed
#
use DBI;
use HTTP::Request::Common qw(POST);
use LWP::UserAgent;

$utils_path = $ENV{'CODENDI_UTILS_PREFIX'} || "/usr/share/tuleap/src/utils";
require $utils_path."/include.pl";
require $utils_path."/group.pl";
require $utils_path."/svn/svn-checkins.pl";
require $utils_path."/hudson.pl";

&db_connect;

# retrieve the group_id
my $gname = $repos;
$gname =~ s|.*/||; # Remove everything until the last slash
my $group_id = &set_group_info_from_name($gname);

my $codendi_srv;
if ($sys_https_host) {
  $codendi_srv="https://$sys_https_host";
} else {
  $codendi_srv="http://$sys_default_domain";
}

my $mod_url = $codendi_srv."/svn/viewvc.php/%s?r1=%s&r2=%s&roottype=svn&root=$gname&diff_format=h&pathrev=$rev";
my $add_url  = $codendi_srv."/svn/viewvc.php/%s?revision=$rev&view=log&roottype=svn&root=$gname&pathrev=$rev";

my $no_diff = 1; # no inline diff for Codendi

# arrays to store all references.
my %references;

# get the mail header from Codendi DB
$svnmailheader = &svnGroup_mail_header();
if ($svnmailheader eq 'NULL') {
  $svnmailheader = "";
}

if ($debug) {
  print STDERR "group_repo: ", $gname, "\n";
  print STDERR "group_id: ", $group_id, "\n";
  print STDERR "mod_url: ", $mod_url, "\n";
  print STDERR "add_url: ", $add_url, "\n";
  print STDERR "mail header in db: ", $svnmailheader, "\n";
}

######################################################################
# Harvest data using svnlook.


# Change into /var/tmp so that svnlook diff can create its .svnlook
# directory.
chdir($tmp_dir)
  or die "$0: cannot chdir `$tmp_dir': $!\n";

# Get the author, date, and log from svnlook.
my @svnlooklines = &read_from_process($svnlook, 'info', $repos, '-r', $rev);
local $author = shift @svnlooklines;
my $date = shift @svnlooklines;
my $unix_gmtime = date_to_gmtime($date);
shift @svnlooklines;
my @log_for_db=@svnlooklines;
my @log = map { "$_\n" } @svnlooklines;
&extract_xrefs(@log);

# Codendi - Figure out what the real user name and email are
#

my $fullname = "";
my $mailname = "";
my $codexuid = "";

# Test whether LDAP plugin is enabled or not
my $query_plugin_ldap = "SELECT NULL FROM plugin WHERE name='ldap' AND available=1";
my $c_plugin_ldap     = $dbh->prepare($query_plugin_ldap);
my $res_plugin_ldap   = $c_plugin_ldap->execute();
if ($res_plugin_ldap && ($c_plugin_ldap->rows > 0)) {
    use Net::LDAP;
    use Cwd; # needed by ldap account auto create

    # Load LDAP config
    require $utils_path."/ldap.pl";
    my $ldapIncFile = $sys_custompluginsroot.'/ldap/etc/ldap.inc';
    &load_local_config($ldapIncFile);

    &ldap_connect;

    if (ldap_enabled_for_project($group_id)) {
	($fullname, $mailname, $ldap_id) = ldap_get_svncommitinfo_from_login($author);
	if ($ldap_id != -1) {
	    $codexuid = db_get_field('user','ldap_id', $ldap_id, 'user_id');
	    if ($codexuid eq "0") {
		$codexuid = ldap_account_create_auto($ldap_id);
	    }
	}
	if ($debug) {
	    print STDERR "LDAP id: $ldap_id\n";
	    print STDERR "Codex uid: $codexuid\n";
	}
    }
}
 
# if no uid found yet, fallback to tuleap auth
if (!$codexuid) {
    local ($login, $gecos);
    $login = $author;
    if (! $login) {
	($login, $gecos) = (getpwuid ($<))[0,6];
    } else {
	$login = "nobody" if (! $login);
	$gecos = (getpwnam ($login))[6];
    }

    # Determine the mailname and fullname.
    if ($gecos =~ /^([^<]*\s+)<(\S+@\S+)>/) {
	$fullname = $1;
	$mailname = $2;
	$fullname =~ s/\s+$//;
    } else {
	$fullname = $gecos;
	$fullname =~ s/,.*$//;

	local ($hostdomain, $hostname);
	chop($hostdomain = `hostname -f`);
	if ($hostdomain !~ /\./) {
	    chop($hostname = `hostname`);
	    if ($hostname !~ /\./) {
		chop($domainname = `domainname`);
		$hostdomain = $hostname . "." . $domainname;
	    } else {
		$hostdomain = $hostname;
	    }
	}
	$mailname = "$login\@$hostdomain";
    }
    $codexuid = db_get_field('user','user_name', $author, 'user_id');
}

if ($debug) {
  print STDERR "$fullname\n";
  print STDERR "$mailname\n";
}


# Figure out what directories have changed using svnlook.
my @dirschanged = &read_from_process($svnlook, 'dirs-changed', $repos, 
                                     '-r', $rev);

my @unchanged_dirschanged = @dirschanged;

# Retrive emails watching a given path that appears in the list of changed directories

my @svn_events_mailing_lists = ();
foreach my $dirVal (@dirschanged) {
  my @directory_notif = get_emails_by_path($dirVal, $group_id);
  push(@svn_events_mailing_lists, @directory_notif);
}
my @svn_events_notifications = redundancy_grep(\@svn_events_mailing_lists);

$svn_events_notifications = join(',', @svn_events_notifications);
# we put off unvalid email and login
$svnmailto = &filter_valid_logins_and_emails($svn_events_notifications);
if ($svnmailto ne 'NULL' && $svnmailto ne '') {
  push(@{$current_project->{email_addresses}}, $svnmailto);
}

if ($debug) {
  print STDERR "svnmailto: ", $svnmailto, "\n";
}
# Lose the trailing slash in the directory names if one exists, except
# in the case of '/'.
my $rootchanged = 0;
for (my $i=0; $i<@dirschanged; ++$i)
  {
    if ($dirschanged[$i] eq '/')
      {
        $rootchanged = 1;
      }
    else
      {
        $dirschanged[$i] =~ s#^(.+)[/\\]$#$1#;
      }
  }

# Figure out what files have changed using svnlook.
@svnlooklines = &read_from_process($svnlook, 'changed', $repos, '-r', $rev);

# Parse the changed nodes.
my @adds;
my @dels;
my @mods;
my @difflines = ();
my @changed_files = ();
foreach my $line (@svnlooklines)
  {
    my $path = '';
    my $url_path = '';
    my $code = '';
    my $change_file = {};

    # Split the line up into the modification code and path, ignoring
    # property modifications.
    if ($line =~ /^(.).  (.*)$/)
      {
        $code = $1;
        $path = $2;
        $url_path = $2;
        $url_path =~ s/ /%20/g;
	$change_file->{'path'} = $path;
      }

    if ($code eq 'A')
      {
	$change_file->{'state'} = 'A';
        push(@adds, $path);
	push(@difflines,sprintf($add_url,$url_path)) if ($no_diff);
      }
    elsif ($code eq 'D')
      {
	$change_file->{'state'} = 'D';
        push(@dels, $path);
      }
    else
      {
	# Can be M or _
	$change_file->{'state'} = 'M';
        push(@mods, $path);
	push(@difflines,sprintf($mod_url,$url_path,$rev-1,$rev)) if ($no_diff);
      }

    push(@changed_files, $change_file) if ($change_file->{'path'});

  }

if ($debug) {
  print STDERR "Array of files changed: \n";
  print STDERR @changed_files;
}

# Get the diff from svnlook.
# Codendi Specific - no diff output for Codendi - Build the ViewVC URL  instead
my @no_diff_deleted;
if ($no_diff == 0) {
  @no_diff_deleted = $no_diff_deleted ? ('--no-diff-deleted') : ();
  @difflines = &read_from_process($svnlook, 'diff', $repos,
                                   '-r', $rev, @no_diff_deleted);
} else {
  unshift(@difflines,"\nSource code changes:\n");
}

######################################################################
# Modified directory name collapsing.

# Collapse the list of changed directories only if the root directory
# was not modified, because otherwise everything is under root and
# there's no point in collapsing the directories, and only if more
# than one directory was modified.
my $commondir = '';
if (!$rootchanged and @dirschanged > 1)
  {
    my $firstline    = shift @dirschanged;
    my @commonpieces = split('/', $firstline);
    foreach my $line (@dirschanged)
      {
        my @pieces = split('/', $line);
        my $i = 0;
        while ($i < @pieces and $i < @commonpieces)
          {
            if ($pieces[$i] ne $commonpieces[$i])
              {
                splice(@commonpieces, $i, @commonpieces - $i);
                last;
              }
            $i++;
          }
      }
    unshift(@dirschanged, $firstline);

    if (@commonpieces)
      {
        $commondir = join('/', @commonpieces);
        my @new_dirschanged;
        foreach my $dir (@dirschanged)
          {
            if ($dir eq $commondir)
              {
                $dir = '.';
              }
            else
              {
                $dir =~ s#^$commondir/##;
              }
            push(@new_dirschanged, $dir);
          }
        @dirschanged = @new_dirschanged;
      }
  }

# reduce subject only to 3 dirs max (avoid never ending subject)
my $dirlist = '';
if (@dirschanged > 3)
  {
    $dirlist = $dirschanged[0]." ".$dirschanged[1]." ".$dirschanged[2]." ...";
  }
else
  {
    $dirlist = join(' ', @dirschanged);
  }

$dirlist =~ s/\n//;

######################################################################
# Assembly of log message.

# Put together the body of the log message.
my @body;
my $goto_link = "$codendi_srv/goto?key=rev&val=$rev&group_id=$group_id";

if (&isGroupUsingTruncatedMails) {
  push(@body, "There was an update on $sys_fullname for you: $goto_link");
} else {
  push(@body, sprintf("SVN Repository: %s\n",$repos));
  push(@body, sprintf("Changes by:     %s  on %s\n","$fullname <$mailname>", $date));
  push(@body, "New Revision:   $rev  $goto_link\n");
  push(@body, "\n");

  # Changes by SL : we first add the log message to the mail body before adding
  # the list of the affected files.
  push(@body, "\nLog message:\n");
  push(@body, @log);

  if (@adds)
    {
      @adds = sort @adds;
      push(@body, "\nAdded files:\n");
      push(@body, map { "   $_\n" } @adds);
    }
  if (@dels)
    {
      @dels = sort @dels;
      push(@body, "\nRemoved files:\n");
      push(@body, map { "   $_\n" } @dels);
    }
  if (@mods)
    {
      @mods = sort @mods;
      push(@body, "\nModified files:\n");
      push(@body, map { "   $_\n" } @mods);
    }

  push(@body, map { /[\r\n]+$/ ? $_ : "$_\n" } @difflines);
  push(@body, map { "$_\n" } &format_xref(@log));
}

if ($debug) {
  print STDERR @head;
  print STDERR @body;
}
# Go through each project and see if there are any matches for this
# project.  If so, send the log out.
foreach my $project (@project_settings_list) {
    my $match_re = $project->{match_re};
    my $match    = 0;
    foreach my $path (@dirschanged, @adds, @dels, @mods) {
        if ($path =~ $match_re) {
            $match = 1;
            last;
        }
    }

    next unless $match;

    my @email_addresses = @{$project->{email_addresses}};
    my $userlist        = join(' ', @email_addresses);
    my $from_address    = $project->{from_address};
    my $hostname        = $project->{hostname};
    my $log_file        = $project->{log_file};
    my $reply_to        = $project->{reply_to};
    my $subject;

    if (&isGroupUsingTruncatedMails) {
      $subject = "New SVN notification on $sys_fullname";
    } else {
      if ($commondir ne '') {
        $subject = "r$rev - in $commondir: $dirlist";
      } else {
        $subject = "r$rev - $dirlist";
      }

      my $subject_prefix  = $project->{subject_prefix};
      if ($subject_prefix =~ /\w/)
        {
          $subject = "$subject_prefix $subject";
        }
    }
    $subject = $svnmailheader.$subject;
    # Remove newlines from subject:
    $subject =~ s/\n//g;
    #Encode subject
    $subject = encode("MIME-Header", decode("UTF-8", $subject));

    my $mail_from = $mailname;

    if ($from_address =~ /\w/)
      {
        $mail_from = $from_address;
      }
    elsif ($hostname =~ /\w/)
      {
        $mail_from = "$mail_from\@$hostname";
      }

    my @head;
    push(@head, "From: $mail_from\n");
    push(@head, "Subject: $subject\n");
    push(@head, "Reply-to: $reply_to\n") if $reply_to;

    ### Below, we set the content-type etc, but see these comments
    ### from Greg Stein on why this is not a full solution.
    #
    # From: Greg Stein <gstein@lyra.org>
    # Subject: Re: svn commit: rev 2599 - trunk/tools/cgi
    # To: dev@subversion.tigris.org
    # Date: Fri, 19 Jul 2002 23:42:32 -0700
    # 
    # Well... that isn't strictly true. The contents of the files
    # might not be UTF-8, so the "diff" portion will be hosed.
    # 
    # If you want a truly "proper" commit message, then you'd use
    # multipart MIME messages, with each file going into its own part,
    # and labeled with an appropriate MIME type and charset. Of
    # course, we haven't defined a charset property yet, but no biggy.
    # 
    # Going with multipart will surely throw out the notion of "cut
    # out the patch from the email and apply." But then again: the
    # commit emailer could see that all portions are in the same
    # charset and skip the multipart thang. 
    # 
    # etc etc
    # 
    # Basically: adding/tweaking the content-type is nice, but don't
    # think that is the proper solution.

    push(@head, "Content-Type: text/plain; charset=UTF-8\n");
    push(@head, "Content-Transfer-Encoding: 8bit\n");
    
    my @emails_to_notify = split(',', $userlist);

    foreach my $email_address (@emails_to_notify) {

        my @head_for_email = @head;
        push(@head_for_email, "To: $email_address\n");
        push(@head_for_email, "\n");

        if ($sendmail =~ /\w/ and @email_addresses) {
            # Open a pipe to sendmail.
            my $command = "$sendmail $email_address";
            if (open(SENDMAIL, "| $command")) {
                print SENDMAIL @head_for_email, @body;
                close SENDMAIL
                  or warn "$0: error in closing `$command' for writing: $!\n";
            }
            else {
                warn "$0: cannot open `| $command' for writing: $!\n";
            }
        }

        # Dump the output to logfile (if its name is not empty).
        if ($log_file =~ /\w/) {
            if (open(LOGFILE, ">> $log_file")) {
                print LOGFILE @head_for_email, @body;
                close LOGFILE
                  or warn "$0: error in closing `$log_file' for appending: $!\n";
            }
            else {
                warn "$0: cannot open `$log_file' for appending: $!\n";
            }
        }
    }
}

# Now add the Subversion information in the Codendi tracking database
if (&isGroupSvnTracked) {
  $commit_id = db_get_commit($group_id,$repos,$rev,$unix_gmtime,$codexuid,@log_for_db);

  for $file (@changed_files) {
    print STDERR "file_path = ".$file->{'path'}."\n" if $debug;
    print STDERR "file_state = ".$file->{'state'}."\n" if $debug;
    ($filename,$dir,$suffix) = fileparse($file->{'path'},());
    db_add_record($file->{'state'},$commit_id,$repos,$dir,$filename,0,0);
  }
}

# Trigger Continuous Integration build if needed.
&trigger_hudson_builds($group_id, 'svn', @unchanged_dirschanged);

exit 0;

sub usage
{
  warn "@_\n" if @_;
  die "usage: $0 REPOS REVNUM [[-m regex] [options] [email_addr ...]] ...\n",
      "options are\n",
      "  --from email_address  Email address for 'From:' (overrides -h)\n",
      "  -h hostname           Hostname to append to author for 'From:'\n",
      "  -l logfile            Append mail contents to this log file\n",
      "  -m regex              Regular expression to match committed path\n",
      "  -r email_address      Email address for 'Reply-To:'\n",
      "  -s subject_prefix     Subject line prefix\n",
      "\n",
      "This script supports a single repository with multiple projects,\n",
      "where each project receives email only for commits that modify that\n",
      "project.  A project is identified by using the -m command line\n",
      "with a regular expression argument.  If a commit has a path that\n",
      "matches the regular expression, then the entire commit matches.\n",
      "Any of the following -h, -l, -r and -s command line options and\n",
      "following email addresses are associated with this project.  The\n",
      "next -m resets the -h, -l, -r and -s command line options and the\n",
      "list of email addresses.\n",
      "\n",
      "To support a single project conveniently, the script initializes\n",
      "itself with an implicit -m . rule that matches any modifications\n",
      "to the repository.  Therefore, to use the script for a single\n",
      "project repository, just use the other comand line options and\n",
      "a list of email addresses on the command line.  If you do not want\n",
      "a project that matches the entire repository, then use a -m with a\n",
      "regular expression before any other command line options or email\n",
      "addresses.\n";
}

# Return a new hash data structure for a new empty project that
# matches any modifications to the repository.
sub new_project
{
  return {email_addresses => [],
          from_address    => '',
          hostname        => '',
          log_file        => '',
          match_regex     => '.',
          reply_to        => '',
          subject_prefix  => ''};
}

# Start a child process safely without using /bin/sh.
sub safe_read_from_pipe
{
  unless (@_)
    {
      croak "$0: safe_read_from_pipe passed no arguments.\n";
    }

  my $pid = open(SAFE_READ, '-|');
  unless (defined $pid)
    {
      die "$0: cannot fork: $!\n";
    }
  unless ($pid)
    {
      open(STDERR, ">&STDOUT")
        or die "$0: cannot dup STDOUT: $!\n";
      exec(@_)
        or die "$0: cannot exec `@_': $!\n";
    }
  my @output;
  while (<SAFE_READ>)
    {
      s/[\r\n]+$//;
      push(@output, $_);
    }
  close(SAFE_READ);
  my $result = $?;
  my $exit   = $result >> 8;
  my $signal = $result & 127;
  my $cd     = $result & 128 ? "with core dump" : "";
  if ($signal or $cd)
    {
      warn "$0: pipe from `@_' failed $cd: exit=$exit signal=$signal\n";
    }
  if (wantarray)
    {
      return ($result, @output);
    }
  else
    {
      return $result;
    }
}

# Use safe_read_from_pipe to start a child process safely and return
# the output if it succeeded or an error message followed by the output
# if it failed.
sub read_from_process
{
  unless (@_)
    {
      croak "$0: read_from_process passed no arguments.\n";
    }
  my ($status, @output) = &safe_read_from_pipe(@_);
  if ($status)
    {
      return ("$0: `@_' failed with this output:", @output);
    }
  else
    {
      return @output;
    }
}

# Codendi - extract all items that needs to be cross-referenced
# in the log message
sub extract_xrefs {
    
    my (@log) = @_;
    # Use Codendi HTTP API
    my $ua = LWP::UserAgent->new;
    $ua->agent('Codendi Perl Agent');

    if (!$group_id) {
      $group_id=100;
    }
    $type="svn_revision";
    $text=join("\n",@log);

    my $req = POST "$codendi_srv/api/reference/extractCross.php",
      [ group_id => "$group_id", text => "$text", rev_id=>"$rev", login=>"$author", type=>"$type" ];
  

    my $response = $ua->request($req);
    if ($response->is_success) {
      my $desc="";
      my $match="";
      my $link="";
      foreach (split(/\n/,$response->content)) {
        chomp;
        if (! $_) {;} # skip empty lines
        elsif (!$desc) {$desc=$_;}
        elsif (!$match) {$match=$_;}
        else {
          $link=$_;
          $references{"$desc"}{"$match"}=$link;
          print STDERR "Found match: $match\n" if $debug;
          $desc=$match=$link=0;
        }
      }
    }
    else {
      warn $response->status_line;
    }
}

sub format_xref {

  my $desc;
  my $match;
  my $initialized='';
  foreach $desc (sort(keys(%references))) {
    if (!$initialized) {
      push (@text, "");
      push (@text, "References:");
      $initialized=1;
    }
      
    push (@text, "");
    push (@text, "$desc:");
    foreach $match (sort(keys %{$references{"$desc"}})) {
      push (@text," $match: ".$references{"$desc"}{"$match"});
      print STDERR "Match: ".$references{"$desc"}{"$match"} if $debug;
    }
  }
  return @text;
}

# transform time stamp returned by svnlook info in epoch time
# relative to GMT
sub date_to_gmtime() {
  my $svndate = shift;

  # svnlook info return time stamp as
  # 2004-05-11 11:12:22 +0200 (Tue, 11 May 2004)

  ($year,$mon,$mday,$hours,$min,$sec,$plusorminus,$shift_hrs,$shift_min) =
    ($svndate =~ /^(\d+)-(\d+)-(\d+)\s+(\d+):(\d+):(\d+)\s*([+-])(\d\d)(\d\d)/);

  $shift = ($shift_hrs*60+$shift_min)*60;
  $shift = -$shift if ($plusorminus eq '-');
  return (timegm($sec, $min, $hours, $mday, $mon-1, $year) - $shift);
}
