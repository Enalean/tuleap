#!/usr/bin/perl
#
# $Id$
#
# new_parse.pl - new script to parse out the database dumps and
# create/update/delete user accounts on the client machines
#


# use DBI;
use Sys::Hostname;
use Carp;

$hostname = hostname();


# Make sure umask is properly positioned for the
# entire session. Root has umask 022 by default
# causing all the mkdir xxx, 775 to actually 
# create dir with permission 755 !!
# So set umask to 002 for the entire script session 
umask 002;

require("include.pl");  # Include all the predefined functions and variables

&load_local_config();
# This section is used to get the list of active users so that we can customize 
# the SVN access rights... But it is not clear how!
# &db_connect;
# my $active_userlist;
# if ($sys_allow_restricted_users) {
#   # Get comma-separated list of all active users (so not restricted) in the system
#   my $c = $dbh->prepare("SELECT user_name FROM user WHERE status='A'");
#   my $res = $c->execute();
#   while( $active_user=$c->fetchrow_array) {
#     if ($active_userlist) { $active_userlist.=',';}
#     $active_userlist.=$active_user;
#   }
# }


my $user_file = $file_dir . "user_dump";
my $group_file = $file_dir . "group_dump";
my ($uid, $status, $username, $shell, $passwd, $win_passwd, $winnt_passwd, $email, $realname);
my ($gname, $gstatus, $gid, $userlist);
my $server_url;
if ($sys_force_ssl) {
  $server_url="https://$sys_https_host";
} else {
  $server_url="http://$sys_default_domain";
}

# Win accounts are activated if /etc/smbpasswd exists.
my $winaccount_on = -f "/etc/smbpasswd";
my $winempty_passwd = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";

# file templates
my $MARKER_BEGIN = "# !!! CodeX Specific !!! DO NOT REMOVE (NEEDED CODEX MARKER)";
my $MARKER_END   = "# END OF NEEDED CODEX BLOCK";

# See under which user this script is running. It is the user that is
# also going to be used to run viewcvs.cgi. And we need to make it the
# owner of all CVS root directories so the CGI script can browse all
# CVS roots including private ones.  For private groups the viewcvs.cgi
# script will implement its own access control.
my ($cxname) = get_codex_user();

# Open up all the files that we need.
@userdump_array = open_array_file($user_file);
@groupdump_array = open_array_file($group_file);
@passwd_array = open_array_file("/etc/passwd");
@shadow_array = open_array_file("/etc/shadow");
@group_array = open_array_file("/etc/group");
@smbpasswd_array = open_array_file("/etc/smbpasswd") if ($winaccount_on);
@htpasswd_array = open_array_file($ENV{'SF_LOCAL_INC_PREFIX'}."/etc/httpd/conf/htpasswd");

#LJ The file containing all allowed root for CVS server
#
my $cvs_root_allow_file = "/etc/cvs_root_allow";
@cvs_root_allow_array = open_array_file($cvs_root_allow_file);

# Check CVS version (CVS or CVSNT)
my $cvsversion=`cvs -v`;
my $use_cvsnt=0;
my $cvsnt_config_file = "/etc/cvsnt/PServer";

if ($cvsversion =~ /CVSNT/) {
  $use_cvsnt=1;
  @cvsnt_config_array = open_array_file($cvsnt_config_file);
}

#
# Loop through @userdump_array and deal w/ users.
#
print ("\n\n	Processing Users\n\n");
while ($ln = pop(@userdump_array)) {
	chop($ln);
	($uid, $status, $username, $shell, $passwd, $win_passwd, $winnt_passwd, $email, $realname) = split(":", $ln);

	# if win passwords are empty in the SQL database then it means
	# that it's a user that was created before Win Account were put in place
	# Force it to the "X....X" real empty password
	$win_passwd = $winempty_passwd if ($win_passwd eq "");
	$winnt_passwd = $winempty_passwd if ($winnt_passwd eq "");

# LJ commented out because it's all on the same machine for the moment
# The SF site has  a cvs server which names start with cvs. We don't
#
#	if (substr($hostname,0,3) eq "cvs") {
#		$shell = "/bin/cvssh";
#	}
	
	$uid += $uid_add;

	$username =~ tr/A-Z/a-z/;
	
	$user_exists = getpwnam($username);
	
	if ($status eq 'A' && $user_exists) {
		update_user($uid, $username, $realname, $shell, $passwd, $email);
                update_user_group($uid, $username);
		update_winuser($uid, $username, $realname, $win_passwd, $winnt_passwd);
		update_httpuser($username, $passwd);

	} elsif ($status eq 'A' && !$user_exists) {
		add_user($uid, $username, $realname, $shell, $passwd, $email);
		add_winuser($uid, $username, $realname, $win_passwd, $winnt_passwd);
		add_httpuser($username, $passwd);
	
	} elsif ($status eq 'D') {

                # delete the user if it exists. Otherwise it means it has
	        # already been deleted so do nothing
	        if ($user_exists) {
		  delete_user($username);
		  delete_winuser($username);
		  delete_httpuser($username);
		}
		
	} elsif ($status eq 'S' && $user_exists) {
		suspend_user($username);
		suspend_winuser($username);
		suspend_httpuser($username);
		
	} elsif ($status eq 'S' && !$user_exists) {
		print("Error trying to suspend user: $username\n");
		
	} elsif ($username eq 'none') {
		# simply ignore: this is a dummy user
	} else {
		print("Unknown Status Flag: $username\n");
	}
}

#
# Loop through @groupdump_array and deal w/ users.
#
print ("\n\n	Processing Groups\n\n");
while ($ln = pop(@groupdump_array)) {
	chop($ln);
	($gname, $gstatus, $gis_public, $cvs_tracker, $svn_tracker, $gid, $userlist) = split(":", $ln);
	
	$cvs_id = $gid + 50000;
	$gid += $gid_add;

	# Add sourceforge user to the group if it is a private project
	# otherwise Apache won't be able to access the document Root
	# of the project web iste which is not world readable (see below)
	$public_grp = $gis_public && ! -e "$grpdir_prefix/$gname/.CODEX_PRIVATE";
	if ($userlist eq "") {
	  $userlist = "sourceforge" unless $public_grp;
	} else {
	  $userlist .= ",sourceforge" unless $public_grp;
	}

	# make all user names lower case.
	$userlist =~ tr/A-Z/a-z/;

	$group_exists = getgrnam($gname);

	my $group_modified = 0;
	if ($gstatus eq 'A' && $group_exists) {
	        $group_modified = update_group($gid, $gname, $userlist);
	
	} elsif ($gstatus eq 'A' && !$group_exists) {
		add_group($gid, $gname, $userlist);
		
	} elsif ($gstatus eq 'D' && $group_exists) {
		delete_group($gname);

	} elsif ($gstatus eq 'D' && !$group_exists) {
# LJ Why print an error here ? The delete user function leave the D flag in place
# LJ so this error msg always appear when a project has been deleted
#		print("Error trying to delete group: $gname\n");
	  print("Deleted Group: $gname\n");
	}

# LJ Do not test if we are on the CVS machine. It's all on atlas
#	if ((substr($hostname,0,3) eq "cvs") && $gstatus eq 'A' && !(-e "$cvs_prefix/$gname")) {
	if ( $gstatus eq 'A' && !(-e "$cvs_prefix/$gname")) {
		print("Creating a CVS Repository for: $gname\n");
		# Let's create a CVS repository for this group
		$cvs_dir = "$cvs_prefix/$gname";

		# Firce create the repository
		mkdir $cvs_dir, 0775;
		system("/usr/bin/cvs -d$cvs_dir init");
	
		# turn off pserver writers, on anonymous readers
		# LJ - See CVS writers update below. Just create an
		# empty writers file so that we can set up the appropriate
		# ownership right below. We will put names in writers
		# later in the script
		system("echo \"\" > $cvs_dir/CVSROOT/writers");
		$group_modified = 1;

		# LJ - we no longer allow anonymous access by default
		#system("echo \"anonymous\" > $cvs_dir/CVSROOT/readers");
		#system("echo \"anonymous:\\\$1\\\$0H\\\$2/LSjjwDfsSA0gaDYY5Df/:anoncvs_$gname\" > $cvs_dir/CVSROOT/passwd");

                if (! $use_cvsnt) {
                  # LJ But to allow checkout/update to registered users we
                  # need to setup a world writable directory for CVS lock files
                  mkdir "$cvs_dir/.lockdir", 0777;
                  chmod 0777, "$cvs_dir/.lockdir"; # overwrite umask value
                  system("echo  >> $cvs_dir/CVSROOT/config");
                  system("echo '# !!! CodeX Specific !!! DO NOT REMOVE' >> $cvs_dir/CVSROOT/config");
                  system("echo '# Put all CVS lock files in a single directory world writable' >> $cvs_dir/CVSROOT/config");
                  system("echo '# directory so that any CodeX registered user can checkout/update' >> $cvs_dir/CVSROOT/config");
                  system("echo '# without having write permission on the entire cvs tree.' >> $cvs_dir/CVSROOT/config");
                  system("echo 'LockDir=$cvs_dir/.lockdir' >> $cvs_dir/CVSROOT/config");
                  # commit changes to config file (directly with RCS)
                  system("cd $cvs_dir/CVSROOT; rcs -q -l config; ci -q -m\"CodeX modifications\" config; co -q config");
                }

                # setup loginfo to make group ownership every commit
                # commit changes to config file (directly with RCS)
                if ($use_cvsnt) {
                  # use DEFAULT because there is an issue with multiple 'ALL' lines with cvsnt.
                  system("echo \"DEFAULT chgrp -f -R  $gname $cvs_dir\" > $cvs_dir/CVSROOT/loginfo");
                } else {
                  system("echo \"ALL (cat;chgrp -R $gname $cvs_dir)>/dev/null 2>&1\" > $cvs_dir/CVSROOT/loginfo");
                }
                system("cd $cvs_dir/CVSROOT; rcs -q -l loginfo; ci -q -m\"CodeX modifications\" loginfo; co -q loginfo");

		# put an empty line in in the valid tag cache (means no tag yet)
		# (this file is not under version control so don't check it in)
		system("echo \"\" > $cvs_dir/CVSROOT/val-tags");
		chmod 0664, "$cvs_dir/CVSROOT/val-tags";

		# set group ownership, codex user
		system("chown -R $cxname:$gid $cvs_dir");
		system("chmod g+rw $cvs_dir");

		# And finally add a user for this repository
		push @passwd_array, "anoncvs_$gname:x:$cvs_id:$gid:Anonymous CVS User for $gname:$cvs_prefix/$gname:/bin/false\n";
	}

	# LJ if the CVS repo has just been created or the user list
	# in the group has been modified then update the CVS
	# writer file

	if ($group_modified) {
	  # LJ On atlas writers go through pserver as well so put
	  # group members in writers file. Do not write anything
	  # in the CVS passwd file. The pserver protocol will fallback
	  # on /etc/passwd for user authentication
	  my $cvswriters_file = "$cvs_prefix/$gname/CVSROOT/writers";
	  open(WRITERS,"+>$cvswriters_file")
	    or croak "Can't open CVS writers file $cvswriters_file: $!";  
	  print WRITERS join("\n",split(",",$userlist)),"\n";
	  close(WRITERS);
	}
	## cvs backend
	if (($cvs_tracker) && ($gstatus eq 'A')){
	  # hook for commit tracking in cvs loginfo file
	  $cvs_dir = "$cvs_prefix/$gname";
	  # if $cvs_dir/CVSROOT/loginfo contains block break;
	  $filename = "$cvs_dir/CVSROOT/loginfo";
	  open (FD, $filename) ;
	  @file_array = <FD>;
	  close(FD);
	  $blockispresent = 0;
	  foreach (@file_array) {
	    $blockispresent = $blockispresent || ($_ eq "$MARKER_BEGIN\n");
	  }
	  if (! $blockispresent)
	    {
	      system("echo \"$MARKER_BEGIN\" >> $cvs_dir/CVSROOT/loginfo");
              if ($use_cvsnt) {
                system("echo \"ALL /usr/local/bin/log_accum -T $gname -C $gname -U $server_url/cvs/viewcvs.php/ -s %{sVv}\" >> $cvs_dir/CVSROOT/loginfo");
              } else {
                system("echo \"ALL (/usr/local/bin/log_accum -T $gname -C $gname -U $server_url/cvs/viewcvs.php/ -s %{sVv})>/dev/null 2>&1\" >> $cvs_dir/CVSROOT/loginfo");
              }	 
	      system("echo \"$MARKER_END\" >> $cvs_dir/CVSROOT/loginfo");
	      system("cd $cvs_dir/CVSROOT; rcs -q -l loginfo; ci -q -m\"CodeX modifications: entering log_accum from group fields (cvs_tracker/cvs_events)\" loginfo; co -q loginfo");
	    }

	  # hook for commit tracking in cvs commitinfo file
	  $cvs_dir = "$cvs_prefix/$gname";
	  # if $cvs_dir/CVSROOT/commitinfo contains block break;
	  $filename = "$cvs_dir/CVSROOT/commitinfo";
	  open (FD, $filename) ;
	  @file_array = <FD>;
	  close(FD);
	  $blockispresent = 0;
	  foreach (@file_array) {
	    $blockispresent = $blockispresent || ($_ eq "$MARKER_BEGIN\n");
	  }
	  if (! $blockispresent)
	    {
	      system("echo \"$MARKER_BEGIN\" >> $cvs_dir/CVSROOT/commitinfo");
	      system("echo \"ALL /usr/local/bin/commit_prep -T $gname -r\" >> $cvs_dir/CVSROOT/commitinfo");
	      system("echo \"$MARKER_END\" >> $cvs_dir/CVSROOT/commitinfo");
	      system("cd $cvs_dir/CVSROOT; rcs -q -l commitinfo; ci -q -m\"CodeX modifications: entering commit_prep from group fields (cvs_tracker/cvs_events)\" commitinfo; co -q commitinfo");
	    }
	}
	#
	# Private directories are set to be unreadable, unwritable,
	# and untraversable.  The project home and cvs root directories
	# are private if either:
	# (1) The project is private
	# (2) The directory contains a file named .CODEX_PRIVATE
	#
	if ($gstatus eq 'A') {
	  my ($cvsmode, $grpmode, $new_cvsmode, $new_grpmode);
	  my ($public_cvs, $public_grp);

	  ($d,$d,$cvsmode) = stat("$cvs_prefix/$gname");
	  ($d,$d,$grpmode) = stat("$grpdir_prefix/$gname");

	  $public_cvs = $gis_public && ! -e "$cvs_prefix/$gname/.CODEX_PRIVATE";
	  $public_grp = $gis_public && ! -e "$grpdir_prefix/$gname/.CODEX_PRIVATE";

	  if ($public_cvs) {
	    $new_cvsmode = ($cvsmode | 0005);
	  } else {
	    $new_cvsmode = ($cvsmode & ~0007);
	  }

	  if ($public_grp) {
	    $new_grpmode = ($grpmode | 0005);
	  } else {
	    $new_grpmode = ($grpmode & ~0007);
	  }

	  chmod $new_cvsmode,"$cvs_prefix/$gname" if ($cvsmode != $new_cvsmode);
	  chmod $new_grpmode,"$grpdir_prefix/$gname" if ($grpmode != $new_grpmode);
        }

	# Create Subversion repository if needed
	$svn_dir = "$svn_prefix/$gname";
	if ( $gstatus eq 'A' && !(-e "$svn_prefix/$gname")) {
	  print("Creating a Subversion Repository for: $gname\n");

	  # Let's create a subversion repository for this group
	  mkdir $svn_dir, 0775;
	  system("/usr/bin/svnadmin create $svn_dir");
	  $group_modified = 1;

	  # set group ownership, codex user
	  system("chown -R $cxname:$gid $svn_dir");
	  system("chmod g+rw $svn_dir");

	}

	# update Subversion DAV access control file if needed
	my $svnaccess_file = "$svn_prefix/$gname/.SVNAccessFile";
        # This test will be removed if we need to list active/restricted users in the SVN auth file
        $filetime= ($dev,$ino,$mode,$nlink,$uid,$gid,$rdev,$size,
         $atime,$mtime,$ctime,$blksize,$blocks)
          = (stat($filename))[9];
        if ($group_modified ||
            ($gstatus eq 'A' && !(-e "$svnaccess_file")) ||
            ((stat($0))[9] > (stat("$svnaccess_file"))[9]) ) { # i.e. this script has been modified since last update
          my $custom_perm=0;
          my $custom_lines;
          my $public_svn = $gis_public && ! -e "$svn_prefix/$gname/.CODEX_PRIVATE";
          
          # Retrieve custom permissions, if any
          if (-e "$svnaccess_file") {
            open(SVNACCESS,"$svnaccess_file");
            while (<SVNACCESS>) {
              if ($custom_perm) {
                $custom_lines.=$_;
              } else {
                if (m/END CODEX DEFAULT SETTINGS/) {$custom_perm=1;}
              }
            }
            close(SVNACCESS);
          }
          
          if (-d "$svn_prefix/$gname") {
            open(SVNACCESS,">$svnaccess_file")
              or croak "Can't open Subversion access file $svnaccess_file: $!";
            # if you change these block markers also change them in
            # SF/www/svn/svn_utils.php
            print SVNACCESS "# BEGIN CODEX DEFAULT SETTINGS - DO NOT REMOVE\n";
            print SVNACCESS "[groups]\n";
            print SVNACCESS "members = ",$userlist,"\n\n";
            print SVNACCESS "[/]\n";
            if ($sys_allow_restricted_users) {
              print SVNACCESS "* = \n"; # deny all access by default
              # we don't know yet how to enable read access to all active users,
              # and deny it to all restricted users...
            } else {
              if ($public_svn) { print SVNACCESS "* = r\n"; }
              else { print SVNACCESS "* = \n";}
            }
            print SVNACCESS "\@members = rw\n";
            print SVNACCESS "# END CODEX DEFAULT SETTINGS\n";
            if ($custom_perm) { print SVNACCESS $custom_lines;}
            close(SVNACCESS);
            
            # set group ownership, codex user as owner so that
            # PHP scripts can write to it directly
            system("chown -R $cxname:$gid $svnaccess_file");
            system("chmod g+rw $svnaccess_file");
          }
        }

	# Put in place the svn post-commit hook for email notification
	# if not present (if the file does not exist it is created)
	$postcommit_file = "$svn_dir/hooks/post-commit";
	if (($svn_tracker) && ($gstatus eq 'A')) {
	  open (FD, "+>>$postcommit_file") ;
	  $blockispresent = 0;
	  while (<FD>) {
	    if ($_ eq "$MARKER_BEGIN\n") { $blockispresent = 1; last; }
	  }
	  if (! $blockispresent) {
	    print FD "#!/bin/sh\n";
	    print FD "$MARKER_BEGIN\n";
	    print FD "REPOS=\"\$1\";REV=\"\$2\"\n";
	    print FD "/usr/local/bin/commit-email.pl \"\$REPOS\" \"\$REV\"\n";
	    print FD "$MARKER_END\n";
	    system("chown -R $cxname:$gid $postcommit_file");
	    system("chmod 775 $postcommit_file");
	  }
	  close(FD);
	}
      }

#
# Now write out the new files
#
write_array_file("/etc/passwd", @passwd_array);
write_array_file("/etc/shadow", @shadow_array);
write_array_file("/etc/group", @group_array);
write_array_file("/etc/smbpasswd", @smbpasswd_array) if ($winaccount_on);
write_array_file($ENV{'SF_LOCAL_INC_PREFIX'}."/etc/httpd/conf/htpasswd", @htpasswd_array);
write_array_file($cvs_root_allow_file, @cvs_root_allow_array);

if ($use_cvsnt) {
  # Write cvsroot list in CVSNT config file
  open FILE,">$cvsnt_config_file";
  print FILE "# CodeX CVSROOT directory list: do not edit this list! modify /etc/cvs_root_allow instead\n";
  my $n=0;
  foreach(@cvs_root_allow_array) {
    print FILE "Repository$n=$_";
    $n++;
  }
  $cvsnt_marker="DON'T EDIT THIS LINE - END OF CODEX BLOCK";
  print FILE "# End of CodeX CVSROOT directory list: you may change options below $cvsnt_marker\n";
  
  # and recopy other configuration instructions
  my $configlines=0;
  foreach(@cvsnt_config_array) {
    print FILE if ($configlines);
    $configlines=1 if (/$cvsnt_marker/);
  }
  close FILE;
}


###############################################
# Begin functions
###############################################

#############################
# User Add Function
#############################
sub add_user {
	my ($uid, $username, $realname, $shell, $passwd, $email) = @_;
	my $skel_array = ();
	
	$home_dir = $homedir_prefix.$username;

	print("Making a User Account for : $username\n");
		
	push @passwd_array, "$username:x:$uid:$uid:$realname <$email>:$home_dir:$shell\n";
	push @shadow_array, "$username:$passwd:$date:0:99999:7:::\n";
	push @group_array, "$username:x:$uid:\n";

	# LJ Couple of modifications here
	# Now lets create the homedir and copy the contents of
	# /etc/skel_codex into it. The change the ownership
	unless (-d "$home_dir") {
            mkdir $home_dir, 0751;
	    if (-d "/etc/skel_codex") {
	        system("cd /etc/skel_codex; tar cf - . | (cd  $home_dir ; tar xf - )");
	    }
            #chown $uid, $uid, $home_dir;
	    system("chown -R $uid.$uid $home_dir");
        }
}

sub add_winuser {
	my ($uid, $username, $realname, $win_passwd, $winnt_passwd) = @_;
	
	return if (!$winaccount_on);

	$win_date = sprintf("%08X", time());
		
	push @smbpasswd_array, "$username:$uid:$win_passwd:$winnt_passwd:[U          ]:LCT-$win_date:$realname\n";

}

sub add_httpuser {
	my ($username, $passwd) = @_;
	push @htpasswd_array, "$username:$passwd\n";
}


#############################
# User Add Function
#############################
sub update_user {
	my ($uid, $username, $realname, $shell, $passwd, $email) = @_;
	my ($p_username, $p_junk, $p_uid, $p_gid, $p_realname, $p_homedir, $p_shell);
	my ($s_username, $s_passwd, $s_date, $s_min, $s_max, $s_inact, $s_expire, $s_flag, $s_resv, $counter);
	
	print("Updating Account for: $username\n");
	
	$counter = 0;
	my $found   = 0;
	foreach (@passwd_array) {
		($p_username, $p_junk, $p_uid, $p_gid, $p_realname_email, $p_homedir, $p_shell) = split(":", $_);
		
		if ($uid == $p_uid) {
                  $found = 1;
		  $passwd_array[$counter] = "$username:x:$uid:$uid:$realname <$email>:$p_homedir:$shell\n";
			last;
		}
		$counter++;
	}
	
	$counter = 0;
	foreach (@shadow_array) {
		($s_username, $s_passwd, $s_date, $s_min, $s_max, $s_inact, $s_expire, $s_flag, $s_resv) = split(":", $_);
		if ($username eq $s_username) {
			if ($passwd ne $s_passwd) {
				$shadow_array[$counter] = "$username:$passwd:$s_date:$s_min:$s_max:$s_inact:$s_expire:$s_flag:$s_resv";
			}
			last;
		}
		$counter++;
	}

	# if account is missing from any of the password file
	# then add it again (it should already be in there but...)
	add_user($uid, $username, $realname, $shell, $passwd, $email) unless $found;

}

# synchronize passwd and group files (one group for each user)
sub update_user_group {
        my ($uid, $username) = @_;
        # Check that corresponding group exists (useful after a crash...)
        if (!getgrnam($username)) {
          push @group_array, "$username:x:$uid:\n";
        }
}


sub update_winuser {
  ($uid, $username, $realname, $win_passwd, $winnt_passwd) = @_;

  my ($p_username, $p_uid, $p_win_passwd, $p_winnt_passwd,$p_account_bits,
      $p_last_set_time, $p_realname);
	
  return if (!$winaccount_on);

  my $counter = 0;
  my $found   = 0;
  foreach (@smbpasswd_array) {
    ($p_username, $p_uid, $p_win_passwd, $p_winnt_passwd,$p_account_bits,
     $p_last_set_time, $p_realname) = split(":", $_);

    if ($uid == $p_uid) {
      $found = 1;
      $win_date = sprintf("%08X", time());

      if ($win_passwd ne $p_win_passwd) {
	$smbpasswd_array[$counter] = "$username:$uid:$win_passwd:$winnt_passwd:[U          ]:LCT-$win_date:$realname\n";
      }
      last;
    }
    $counter++;
  }	

  # if account not found then create the entry again
  add_winuser($uid, $username, $realname, $win_passwd, $winnt_passwd) unless $found;
}

sub update_httpuser {
  my ($username, $passwd) = @_;

  my ($p_username, $p_passwd);

  my $counter = 0;
  my $found   = 0;
  foreach (@htpasswd_array) {
    ($p_username, $p_passwd) = split(":", $_);

    if ($username eq $p_username) {
      $found = 1;
      if ($passwd ne $p_passwd) {
	$htpasswd_array[$counter] = "$username:$passwd\n";
      }
      last;
    }
    $counter++;
  }

  add_httpuser($username, $passwd) unless $found;
	
}


#############################
# User Deletion Function
#############################
sub delete_user {
	my ($username, $junk, $uid, $gid, $realname, $homedir, $shell, $counter);
	my $this_user = shift(@_);
        $counter = 0;	
	foreach (@passwd_array) {
		($username, $junk, $uid, $gid, $realname, $homedir, $shell) = split(":", $_);
		if ($this_user eq $username) {
			$passwd_array[$counter] = '';
		}
		$counter++;
	}

        $counter = 0;	
	foreach (@shadow_array) {
		($username) = split(":", $_);
		if ($this_user eq $username) {
			$shadow_array[$counter] = '';
		}
		$counter++;
	}

        $counter = 0;	
	foreach (@group_array) {
		($groupname) = split(":", $_);
		if ($this_user eq $groupname) {
			$group_array[$counter] = '';
		}
		$counter++;
        }
	
	print("Deleting User : $this_user\n");
	system("cd $homedir_prefix ; /bin/tar -czf $tar_dir/$username.tar.gz $username");
	system("rm -fr $homedir_prefix/$username");
}

sub delete_winuser {
	my $this_user = shift(@_);
	my ($username, $uid, $win_passwd, $winnt_passwd,$account_bits,
	    $last_set_time, $realname);
	my $counter = 0;
	
	return if (!$winaccount_on);

	foreach (@smbpasswd_array) {
	  ($username, $uid, $win_passwd, $winnt_passwd,$account_bits,
	    $last_set_time, $realname) = split(":", $_);

	  if ($this_user eq $username) {
	    $smbpasswd_array[$counter] = '';
	  }
	  $counter++;
	}
	
}

sub delete_httpuser {
  my $this_user = shift(@_);
  my ($username, $p_passwd);
  my $counter = 0;

  foreach (@htpasswd_array) {
    ($username,$p_passwd) = split(":", $_);

    if ($this_user eq $username) {
      $htpasswd_array[$counter] = '';
    }
    $counter++;
  }
}

#############################
# User Suspension Function
#############################
sub suspend_user {
	my $this_user = shift(@_);
	my ($s_username, $s_passwd, $s_date, $s_min, $s_max, $s_inact, $s_expire, $s_flag, $s_resv, $counter);
	
        $counter =0;	
	foreach (@shadow_array) {
	  ($s_username, $s_passwd, $s_date, $s_min, $s_max, $s_inact, $s_expire, $s_flag, $s_resv) = split(":", $_);
	  if ($this_user eq $s_username) {
	    # if already suspended then give up
	    if ($s_passwd =~ /^!!/) {
		last;
	    } else {
	        my $new_passwd = "!!" . $s_passwd;
	        $shadow_array[$counter] = "$s_username:$new_passwd:$s_date:$s_min:$s_max:$s_inact:$s_expire:$s_flag:$s_resv";
	    }
	  }
	  $counter++;
	}
}

sub suspend_winuser {
	my $this_user = shift(@_);
	my ($username, $uid, $win_passwd, $winnt_passwd,$account_bits,
	    $last_set_time, $realname);
	
	return if (!$winaccount_on);

	my $counter = 0;
	my $new_account_bits = "[DU         ]"; # D flag for suspended
	foreach (@smbpasswd_array) {
	  ($username, $uid, $win_passwd, $winnt_passwd,$account_bits,
	    $last_set_time, $realname) = split(":", $_);

	  if ($this_user eq $username) {
	    # if already suspended then give up
	    last if ($account_bits =~ /DU/);
	    $smbpasswd_array[$counter] = "$username:$uid:$win_passwd:$winnt_passwd:$new_account_bits:$last_set_time:$realname";
	  }
	  $counter++;
	}

}

sub suspend_httpuser {
  my $this_user = shift(@_);
  my ($username, $p_passwd);
  my $counter = 0;

  foreach (@htpasswd_array) {
    ($username,$p_passwd) = split(":", $_);

    if ($this_user eq $username) {
      $htpasswd_array[$counter] = '$username:!!';
    }
    $counter++;
  }
}

#############################
# Group Add Function
#############################
sub add_group {  
	my ($gid, $gname, $userlist) = @_;
	my ($log_dir, $cgi_dir, $ht_dir, $cvs_dir, $cvs_id);
	
	$group_dir = $grpdir_prefix.$gname;
	$log_dir = $group_dir."/log";
	$cgi_dir = $group_dir."/cgi-bin";
	$ht_dir = $group_dir."/htdocs";
	$ftp_frs_group_dir = $ftp_frs_dir_prefix.$gname;
	$ftp_anon_group_dir = $ftp_anon_dir_prefix.$gname;

	print("Making a Group for : $gname\n");
		
	push @group_array, "$gname:x:$gid:$userlist\n";

# LJ Add the CVS repo in the allowed root for CVS server
	push @cvs_root_allow_array, "$cvs_prefix/$gname\n";
	
# LJ Comment the if. Does not apply on CodeX
#	if (substr($hostname,0,3) ne "cvs") {

		# Now lets create the group's homedir.
                # (put the SGID sticky bit on all dir so that all files
                # in there are owned by the project group and not
                # the user own group
                # For some reason setting the SGID bit in mkdir doesn't work
                # (perl bug ?) hence the chmod
		mkdir $group_dir, 0775;
		mkdir $log_dir, 0775;
		mkdir $cgi_dir, 0775;
		mkdir $ht_dir, 0775;
		chown $dummy_uid, $gid, ($group_dir, $log_dir, $cgi_dir, $ht_dir);
                chmod 02775, ($group_dir, $log_dir, $cgi_dir, $ht_dir);

		# Copy the default empty page for Web site
		# Check if a custom page exists
	$custom_homepage = $ENV{'SF_LOCAL_INC_PREFIX'}."/etc/codex/site-content/en_US/others/default_page.php";
	$homepage = $sys_incdir."/en_US/others/default_page.php";

        ($dev,$ino) = stat($custom_homepage);
        if ( $ino ) {
            # A custom file exists
    		system("cp $custom_homepage $ht_dir/index.php");
        } else {
            # Use the standard file
    		system("cp $homepage $ht_dir/index.php");
        }
		
		chown $dummy_uid, $gid, "$ht_dir/index.php";
		chmod 0664, "$ht_dir/index.php";

		# Now lets create the group's ftp homedir for anonymous ftp space
   	        # (this one must be owned by the project gid so that all project
                # admins can work on it (upload, delete, etc...)
		mkdir $ftp_anon_group_dir, 0775;
		chown $dummy_uid, $gid, "$ftp_anon_group_dir";

		# Now lets create the group's ftp homedir for file release space
   	        # (this one has limited write access to project members and read
	        # read is also for project members as well (download has to go
	        # through the Web for accounting and traceability purpose)
		mkdir $ftp_frs_group_dir, 0771;
		chown $dummy_uid, $gid, "$ftp_frs_group_dir";
		
#	 }

}

#############################
# Group Update Function
#############################
sub update_group {
	my ($gid, $gname, $userlist) = @_;
	my ($p_gname, $p_junk, $p_gid, $p_userlist);
# LJ modification to return TRUE if user list has changed
	my $modified = 0;
        my $counter = 0;

	print("Updating Group: $gname\n");
	
	foreach (@group_array) {
		($p_gname, $p_junk, $p_gid, $p_userlist) = split(":", $_);
		chomp $p_userlist;
		if ($gid == $p_gid) {
			if ($userlist ne $p_userlist) {
				$group_array[$counter] = "$gname:x:$gid:$userlist\n";
				$modified = 1;
			}
		}
		$counter++;
	}

	return $modified;
}

#############################
# Group Delete Function
#############################
sub delete_group {
	my ($gname, $x, $gid, $userlist, $counter);
	my $this_group = shift(@_);
	$counter = 0;
	
	foreach (@group_array) {
		($gname, $x, $gid, $userlist) = split(":", $_);
		if ($this_group eq $gname) {
			$group_array[$counter] = '';
		}
		$counter++;
	}

	# LJ delete CVS repository from the list of CVS allowed root
	$counter = 0;
	foreach (@cvs_root_allow_array) {
	  if ( $cvs_root_allow_array[$counter] eq "$cvs_prefix/$gname") {
	    $cvs_root_allow_array[$counter] = '';
	  }
	  $counter++;
	}

# LJ Comment. Useless on CodeX
#	if (substr($hostname,0,3) ne "cvs") {
		print("Deleting Group: $this_group\n");
		system("cd $grpdir_prefix ; /bin/tar -czf $tar_dir/$this_group.tar.gz $this_group");
		system("rm -fr $grpdir_prefix/$this_group");

# LJ And do the same for the CVS directory
		system("cd $cvs_prefix ; /bin/tar -czf $tar_dir/$this_group-cvs.tar.gz $this_group");
		system("rm -fr $cvs_prefix/$this_group");


#	}
}

