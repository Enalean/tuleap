#!/usr/bin/perl
#
# 
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
require("./cvs1/cvs_watch.pl"); # Include predefined functions for watch mode

$smb_passwd_file="/etc/samba/smbpasswd";


##############################
# Index system files for faster access.
# (use hash that points to line number)
# hash key depends on file (passwd: id, shadow: username, etc.)
##############################
sub hash_passwd_array {
        my @file_array = @_;
        my %tmp_hash;
        my $counter=0;

        foreach (@file_array) {
          ($name,$junk,$id,$rest) = split(":", $_);
          if (defined $tmp_hash{$id}) { print "/etc/passwd: ID $id already exists, please remove duplicates!\n";}
          $tmp_hash{$id}=$counter;
          $counter++;
        }
        return %tmp_hash;
      }

sub hash_shadow_array {
        my @file_array = @_;
        my %tmp_hash;
        my $counter=0;

        foreach (@file_array) {
          ($name,$rest) = split(":", $_);
          if (defined $tmp_hash{$name}) { print "/etc/shadow: $name already exists, please remove duplicates!\n";}
          $tmp_hash{$name}=$counter;
          $counter++;
        }
        return %tmp_hash;
      }
sub hash_htpasswd_array {
        my @file_array = @_;
        my %tmp_hash;
        my $counter=0;

        foreach (@file_array) {
          ($name, $passwd) = split(":", $_);
          if (defined $tmp_hash{$name}) { print "$apache_htpasswd: $name already exists, please remove duplicates!\n";}
          $tmp_hash{$name}=$counter;
          $counter++;
        }
        return %tmp_hash;
      }
sub hash_smbpasswd_array {
        my @file_array = @_;
        my %tmp_hash;
        my $counter=0;

        foreach (@file_array) {
          ($name, $uid, $rest) = split(":", $_);
          if (defined $tmp_hash{$uid}) { print "$smb_passwd_file: ID $uid already exists, please remove duplicates!\n";}
          $tmp_hash{$uid}=$counter;
          $counter++;
        }
        return %tmp_hash;
      }

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


my $verbose=0;
my $user_file = $dump_dir . "/user_dump";
my $group_file = $dump_dir . "/group_dump";
my $server_file = $dump_dir . "/server_dump";
my ($uid, $unix_status, $status, $username, $shell, $passwd, $win_passwd, $winnt_passwd, $email, $realname);
my ($gname, $gstatus, $gid, $userlist, $ugrouplist);
my $server_url;
if ($sys_force_ssl) {
  $server_url="https://$sys_https_host";
} else {
  $server_url="http://$sys_default_domain";
}

# Win accounts are activated if /etc/samba/smbpasswd exists.
my $winaccount_on = -f "$smb_passwd_file";
my $winempty_passwd = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";

# file templates
my $MARKER_BEGIN = "# !!! CodeX Specific !!! DO NOT REMOVE (NEEDED CODEX MARKER)";
my $MARKER_END   = "# END OF NEEDED CODEX BLOCK";

# See under which user this script is running. It is the user that is
# also going to be used to run viewvc.cgi. And we need to make it the
# owner of all CVS root directories so the CGI script can browse all
# CVS roots including private ones.  For private groups the viewvc.cgi
# script will implement its own access control.
my ($cxname) = get_codex_user();

# PK new variables for simple final abstract
#
my ($up_user, $new_user, $del_user, $suspend_user, $error_user) = ("0","0","0","0","0");
my ($up_group, $new_group, $del_group) = ("0","0","0");


# Open up all the files that we need.
@userdump_array = open_array_file($user_file);
@groupdump_array = open_array_file($group_file);
@passwd_array = open_array_file("/etc/passwd");
@shadow_array = open_array_file("/etc/shadow");
@group_array = open_array_file("/etc/group");
@smbpasswd_array = open_array_file($smb_passwd_file) if ($winaccount_on); # doesn't exist?? XXX
@htpasswd_array = open_array_file($apache_htpasswd);
# Index files (use hash that points to line number)
%passwd_hash = hash_passwd_array(@passwd_array);
%shadow_hash = hash_shadow_array(@shadow_array);
%htpasswd_hash = hash_htpasswd_array(@htpasswd_array);
%smbpasswd_hash = hash_smbpasswd_array(@smbpasswd_array);

#LJ The file containing all allowed root for CVS server
#
@cvs_root_allow_array = open_array_file($cvs_root_allow_file);

# Check CVS version (CVS or CVSNT)
my $cvsversion=`$cvs_cmd -v`;
my $use_cvsnt=0;

if ($cvsversion =~ /CVSNT/) {
  $use_cvsnt=1;
  @cvsnt_config_array = open_array_file($cvsnt_config_file);
}

# Check is the current server is master or satellite
#
my $server_is_master = 0;
if($sys_server_id == 0) {
	$server_is_master = 1;
} else {
	if(! -f $server_file) {
		$server_is_master = 1;
	} else {
		@serverdump_array = open_array_file($server_file);
		my $oneServerFound = 0;
		my ($server_id, $is_master);
		while ($ln = pop(@serverdump_array)) {
		    chop($ln);
		    $oneServerFound = 1;
		    ($server_id, $is_master) = split(":", $ln);
		    if($server_id == $sys_server_id && $is_master == 1) {
			$server_is_master = 1;
		    }
		}
		if($oneServerFound == 0) {
		    $server_is_master = 1;
		}
	}
}

#
# Loop through @userdump_array and deal w/ users.
#
print ("\n\n	Processing Users\n\n");
while ($ln = pop(@userdump_array)) {
	chop($ln);
	($uid, $unix_status, $status, $username, $shell, $passwd, $win_passwd, $winnt_passwd, $email, $realname) = split(":", $ln);

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
	
        # check if user already exists in /etc/passwd and /etc/shadow
	#$user_exists = getpwnam($username); # takes too long...
	$user_exists = 0;
        if ((defined $shadow_hash{$username})&&(defined $passwd_hash{$uid})) {
          my ($p_username, $p_other) = split(":", $passwd_array[$passwd_hash{$uid}]);
          my ($s_username, $s_other) = split(":", $shadow_array[$shadow_hash{$username}]);
          if (($p_username eq $username)&&($s_username eq $username)) {
            $user_exists=1;
          } else { print "ERROR in passwd/shadow file: user '$username' does not seem properly declared\n";}
        }

	$user_active=0;
        if ($status eq 'A' || $status eq 'R') {$user_active=1;}
	if ($unix_status eq 'A' && $user_exists && $user_active) {
		update_user($uid, $username, $realname, $shell, $passwd, $email);
                update_user_group($uid, $username);
		update_winuser($uid, $username, $realname, $win_passwd, $winnt_passwd);
		update_httpuser($username, $passwd);
		++$up_user;

	} elsif ($unix_status eq 'A' && !$user_exists && $user_active) {
		add_user($uid, $username, $realname, $shell, $passwd, $email);
		add_winuser($uid, $username, $realname, $win_passwd, $winnt_passwd);
		add_httpuser($username, $passwd);
		++$new_user;
	
	} elsif ($unix_status eq 'D') {
                # delete the user if it exists. Otherwise it means it has
	        # already been deleted so do nothing
	        if ($user_exists) {
		  delete_user($uid,$username);
		  delete_winuser($uid);
		  delete_httpuser($username);
		  ++$del_user;
		}
		
	} elsif (($unix_status eq 'S' || $status eq 'S') && $user_exists) {
          if (suspend_user($username)) {
            suspend_winuser($uid);
            suspend_httpuser($username);
            ++$suspend_user;
          }
		
	} elsif ($unix_status eq 'S' && !$user_exists) {
		# This might happen e.g. after a server migration 
		# (/etc/passwd not copied but re-generated)
		# Nothing specific to do in this case.
		
	} elsif ($username eq 'none') {
		# simply ignore: this is a dummy user
        } elsif ($user_active) {
		print("Unknown Status Flag: $username\n");
		++$error_user;
	}
}
print ("\n	User Processing Results\n\n");
print "New users accounts      : $new_user\n";
print "Updated user accounts   : $up_user\n";
print "Deleted user accounts   : $del_user\n";
print "Suspended user accounts : $suspend_user\n";
print "User account problems   : $error_user\n";

#
# Loop through @groupdump_array and deal w/ users.
#
print ("\n\n	Processing Groups\n\n");
while ($ln = pop(@groupdump_array)) {
	chop($ln);
	($gname, $gstatus, $gis_public, $cvs_tracker, $cvs_watch_mode, $svn_tracker, $gid, $userlist, $ugrouplist, $file_service, $svn_service) = split(":", $ln);
	
	$cvs_id = $gid + 50000;
	$gid += $gid_add;

	# Add $sys_http_user user to the group if it is a private project
	# otherwise Apache won't be able to access the document Root
	# of the project web iste which is not world readable (see below)
	$public_grp = $gis_public && ! -e "$grpdir_prefix/$gname/.CODEX_PRIVATE";
	if ($userlist eq "") {
	  $userlist = $sys_http_user unless $public_grp;
	} else {
	  $userlist .= ",".$sys_http_user unless $public_grp;
	}

	# make all user names lower case.
	$userlist =~ tr/A-Z/a-z/;
	#$ugrouplist =~ tr/A-Z/a-z/;

	$group_exists = getgrnam($gname);

	my $group_modified = 0;
	if ($gstatus eq 'A' && $group_exists) {
	        $group_modified = update_group($gid, $gname, $userlist);
                ++$up_group;
	
	} elsif ($gstatus eq 'A' && !$group_exists) {
		add_group($gid, $gname, $userlist);
                ++$new_group;
		
	} elsif ($gstatus eq 'D' && $group_exists) {
		delete_group($gname);
		++$del_group;

	} elsif ($gstatus eq 'D' && !$group_exists) {
	  print("Deleted Group: $gname\n") if $verbose;
	}

        # SVN service location
	my ($svn_location, $svn_server_id) = split(",", $svn_service);

	# File service location
	my ($file_location, $file_server_id) = split(",", $file_service);

        $cvs_dir = "$cvs_prefix/$gname";
	if($server_is_master) {
	    if ( $gstatus eq 'A' && !(-e "$cvs_dir")) {
		print("Creating a CVS Repository for: $gname\n");
		# Let's create a CVS repository for this group

		# First create the repository
		mkdir $cvs_dir, 0775;
                if (! $use_cvsnt) {
                  system("$cvs_cmd -d$cvs_dir init");
                } else {
                  # Tell cvsnt not to update /etc/cvsnt/PServer: this is done later by this the script.
                  system("$cvs_cmd -d$cvs_dir init -n");
                }
		
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
		    $lockdir="$cvslock_prefix/$gname";
		    mkdir "$lockdir", 0777;
		    chmod 0777, "$lockdir"; # overwrite umask value
		    system("echo  >> $cvs_dir/CVSROOT/config");
		    system("echo '# !!! CodeX Specific !!! DO NOT REMOVE' >> $cvs_dir/CVSROOT/config");
		    system("echo '# Put all CVS lock files in a single directory world writable' >> $cvs_dir/CVSROOT/config");
		    system("echo '# directory so that any CodeX registered user can checkout/update' >> $cvs_dir/CVSROOT/config");
		    system("echo '# without having write permission on the entire cvs tree.' >> $cvs_dir/CVSROOT/config");
		    system("echo 'LockDir=$lockdir' >> $cvs_dir/CVSROOT/config");
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
                system("cd $cvs_dir/CVSROOT; chown -R $cxname:$gid loginfo*");

		# put an empty line in in the valid tag cache (means no tag yet)
		# (this file is not under version control so don't check it in)
		system("echo \"\" > $cvs_dir/CVSROOT/val-tags");
		chmod 0664, "$cvs_dir/CVSROOT/val-tags";

                if ($use_cvsnt) {
                  # Create history file (not created by default by cvsnt)
                  system("touch $cvs_dir/CVSROOT/history");
                  # Must be writable
                  chmod 0666, "$cvs_dir/CVSROOT/history";
                }

		# set group ownership, codex user
		system("chown -R $cxname:$gid $cvs_dir");
		system("chmod g+rw $cvs_dir");

		# And finally add a user for this repository
                # DEPRECATED: no longer create the login automatically: create it on demand only...
		#push @passwd_array, "anoncvs_$gname:x:$cvs_id:$gid:Anonymous CVS User for $gname:$cvs_dir:/bin/false\n";
	    }
	    if ( $gstatus eq 'A' && (! $use_cvsnt) && !(-e "$cvslock_prefix/$gname")) {
		# Lockdir was deleted? Recreate it.
		$lockdir="$cvslock_prefix/$gname";
		mkdir "$lockdir", 0777;
		chmod 0777, "$lockdir"; # overwrite umask value
	    }

            if ( $gstatus eq 'A' && ($use_cvsnt) && !(-e "$cvs_dir/CVSROOT/history")) {
              # history was deleted (or not created)? Recreate it.
              system("touch $cvs_dir/CVSROOT/history");
              # Must be writable
              chmod 0666, "$cvs_dir/CVSROOT/history";
              system("chown $cxname:$gid $cvs_dir/CVSROOT/history");
            }

	    # LJ if the CVS repo has just been created or the user list
	    # in the group has been modified then update the CVS
	    # writer file

	    if ($group_modified) {
		# On CodeX writers go through pserver as well so put
		# group members in writers file. Do not write anything
		# in the CVS passwd file. The pserver protocol will fallback
		# on /etc/passwd for user authentication
		my $cvswriters_file = "$cvs_dir/CVSROOT/writers";
		open(WRITERS,"+>$cvswriters_file")
		    or croak "Can't open CVS writers file $cvswriters_file: $!";  
		print WRITERS join("\n",split(",",$userlist)),"\n";
		close(WRITERS);
	    }
	    ## cvs backend
	    if (($cvs_tracker) && ($gstatus eq 'A')){
		# hook for commit tracking in cvs loginfo file
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
			system("echo \"ALL $codex_bin_prefix/log_accum -T $gname -C $gname -s %{sVv}\" >> $cvs_dir/CVSROOT/loginfo");
		    } else {
			system("echo \"ALL ($codex_bin_prefix/log_accum -T $gname -C $gname -s %{sVv})>/dev/null 2>&1\" >> $cvs_dir/CVSROOT/loginfo");
		    }	 
		    system("echo \"$MARKER_END\" >> $cvs_dir/CVSROOT/loginfo");
		    system("cd $cvs_dir/CVSROOT; rcs -q -l loginfo; ci -q -m\"CodeX modifications: entering log_accum from group fields (cvs_tracker/cvs_events)\" loginfo; co -q loginfo");
		    system("cd $cvs_dir/CVSROOT; chown -R $cxname:$gid loginfo*");
		}

		# hook for commit tracking in cvs commitinfo file
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
		    system("echo \"ALL $codex_bin_prefix/commit_prep -T $gname -r\" >> $cvs_dir/CVSROOT/commitinfo");
		    system("echo \"$MARKER_END\" >> $cvs_dir/CVSROOT/commitinfo");
		    system("cd $cvs_dir/CVSROOT; rcs -q -l commitinfo; ci -q -m\"CodeX modifications: entering commit_prep from group fields (cvs_tracker/cvs_events)\" commitinfo; co -q commitinfo");
		    system("cd $cvs_dir/CVSROOT; chown -R $cxname:$gid commitinfo*");
		}
	    }


            #
            #  CVS WATCH ON
            #
	    # Add notify command if cvs_watch_mode is on
	    if (($cvs_watch_mode) && ($gstatus eq 'A')){
		$filename = "$cvs_dir/CVSROOT/notify";

		open (FD, $filename) ;
		@file_array = <FD>;
		close(FD);
		$blockispresent = 0;
		foreach (@file_array) {
		    $blockispresent = $blockispresent || ($_ eq "$MARKER_BEGIN\n");
		}
		if (! $blockispresent)
		{
		    system("echo \"$MARKER_BEGIN\" >> $filename");
		    system("echo \"ALL mail %s -s \\\"CVS notification\\\"\" >> $filename");
		    system("echo \"$MARKER_END\" >> $filename");
		    system("cd $cvs_dir/CVSROOT; rcs -q -l notify; ci -q -m\"CodeX modifications: enable notifications\" notify; co -q notify");
		    system("cd $cvs_dir/CVSROOT; chown -R $cxname:$gid notify*");

                    # Apply cvs watch on only if cvs_watch_mode changed to on 
                    print("apply cvs watch on to the project : $gname\n");
                    $id = getpgrp();                # You *must* use a shell that does setpgrp()!
                    &cvs_watch($cvs_dir,$gname,$id,1);
                    system("chown -R $cxname:$gid $cvs_dir");
                    system("chmod g+rw $cvs_dir");
                }
	    }

            #
            #  CVS WATCH OFF
            #

	    # Remove notify command if cvs_watch_mode is off.
	    if ((! $cvs_watch_mode) && ($gstatus eq 'A')){
		$filename = "$cvs_dir/CVSROOT/notify";

		open (FD, $filename) ;
		@file_array = <FD>;
		close(FD);
		$blockispresent = 0;
		$inblock=0;
		$counter=0;
		foreach (@file_array) {
		    $blockispresent = $blockispresent || ($_ eq "$MARKER_BEGIN\n");
		    if ($_ eq "$MARKER_BEGIN\n") { $inblock=1; }
		    if ($inblock) {
			@file_array[$counter]='';
			if ($_ eq "$MARKER_END\n") { $inblock=0; }
		    } 
		    $counter++;
		}
		if ($blockispresent)
		{
		    write_array_file($filename, @file_array );
		    system("cd $cvs_dir/CVSROOT; rcs -q -l notify; ci -q -m\"CodeX modifications: disable notifications\" notify; co -q notify");
		    system("cd $cvs_dir/CVSROOT; chown -R $cxname:$gid notify*");
		}
	    }


	    # Apply cvs watch off only if cvs_watch_mode changed to off
	    if ((!$cvs_watch_mode) && ($gstatus eq 'A') && ($blockispresent))
	    {
        	print("apply cvs watch off to the project : $gname\n");
		$id = getpgrp();                # You *must* use a shell that does setpgrp()!
		&cvs_watch($cvs_dir,$gname,"", "", $id,0);
	    }
	}

        #
        # Subversion
        #

	if(service_available_on_server($server_is_master, $svn_location, $svn_server_id)) {

	    # Create Subversion repository if needed
	    $svn_dir = "$svn_prefix/$gname";
	    if ( $gstatus eq 'A' && !(-e "$svn_prefix/$gname")) {
		print("Creating a Subversion Repository for: $gname\n");

		# Let's create a subversion repository for this group
		mkdir $svn_dir, 0775;
		system("$svnadmin_cmd create $svn_dir --fs-type fsfs");
		$group_modified = 1;

		# set group ownership, codex user
		system("chown -R $cxname:$gid $svn_dir");
		system("chmod g+rw $svn_dir");

	    }


	    #test only
	    $group_modified = 1;

	    # update Subversion DAV access control file if needed
	    my $svnaccess_file = "$svn_prefix/$gname/.SVNAccessFile";
	    
	    if ($group_modified ||
		($gstatus eq 'A' && !(-e "$svnaccess_file")) ||
		( -e "$svn_prefix/$gname/.CODEX_PRIVATE") || # file may be created any time
		((stat($0))[9] > (stat("$svnaccess_file"))[9]) ) { 
		# i.e. this script has been modified since last update
		# This test will be removed if we need to list active/restricted users in the SVN auth file
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
		    # src/www/svn/svn_utils.php
		    print SVNACCESS "# BEGIN CODEX DEFAULT SETTINGS - DO NOT REMOVE\n";
		    print SVNACCESS "[groups]\n";
		    print SVNACCESS "members = ",join(", ", split(",", $userlist)),"\n";

		    @ugroup_array = split(" ",$ugrouplist);
		    while ($ln = pop(@ugroup_array)) {
			# we want username to be in lowercase, but ugroupname keep the original case
			# so we split the line UgroupName = MemBer1,MEMBER2,member3, ...
			@ugroupdef_array = split("=", $ln);
			my $ugroup_members = pop(@ugroupdef_array);
			my $ugroup_name = pop(@ugroupdef_array);
			# and then join the line with the correct case.
			# UgroupName = member1,member2,member3, ...
                        # actually, if there are no members, $ugroup_members contains the the group name 
                        if (($ugroup_members ne '')&&($ugroup_name ne '')) {
                         print SVNACCESS $ugroup_name," = ",lc(join(", ", split(",", $ugroup_members))),"\n";
                       }
		    }
		    print SVNACCESS "\n";

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
		open (FD, "$postcommit_file") ;
		$blockispresent = 0;
		while (<FD>) {
		    if ($_ eq "$MARKER_BEGIN\n") { $blockispresent = 1; last; }
		}
		close(FD);
		if (! $blockispresent) {
		    open (FD, ">>$postcommit_file") ;
		    print FD "#!/bin/sh\n";
		    print FD "$MARKER_BEGIN\n";
		    print FD "REPOS=\"\$1\";REV=\"\$2\"\n";
		    print FD "$codex_bin_prefix/commit-email.pl \"\$REPOS\" \"\$REV\" 2>&1 >/dev/null\n";
		    print FD "$MARKER_END\n";
		    close(FD);
		    system("chown -R $cxname:$gid $postcommit_file");
		    system("chmod 775 $postcommit_file");
		}
	    }
	}

	#
	# Private directories are set to be unreadable, unwritable,
	# and untraversable.  The project home, subversion root and cvs root directories
	# are private if either:
	# (1) The project is private
	# (2) The directory contains a file named .CODEX_PRIVATE
	#
	if ($gstatus eq 'A') {
	    my ($cvsmode, $svnmode, $grpmode, $new_cvsmode, $new_svnmode, $new_grpmode);
	    my ($public_cvs, $public_svn, $public_grp);

	    ($d,$d,$cvsmode) = stat("$cvs_prefix/$gname");
	    ($d,$d,$svnmode) = stat("$svn_prefix/$gname");
	    ($d,$d,$grpmode) = stat("$grpdir_prefix/$gname");

	    $public_cvs = $gis_public && ! -e "$cvs_prefix/$gname/.CODEX_PRIVATE";
	    $public_svn = $gis_public && ! -e "$svn_prefix/$gname/.CODEX_PRIVATE";
	    $public_grp = $gis_public && ! -e "$grpdir_prefix/$gname/.CODEX_PRIVATE";

	    if ($public_cvs) {
		$new_cvsmode = ($cvsmode | 0005);
	    } else {
		$new_cvsmode = ($cvsmode & ~0007);
	    }

	    if ($public_svn) {
		$new_svnmode = ($svnmode | 0005);
	    } else {
		$new_svnmode = ($svnmode & ~0007);
	    }

	    if ($public_grp) {
		$new_grpmode = ($grpmode | 0005);
	    } else {
		$new_grpmode = ($grpmode & ~0007);
	    }

	    if($server_is_master) {
		chmod $new_cvsmode,"$cvs_prefix/$gname" if ($cvsmode != $new_cvsmode);
		chmod $new_grpmode,"$grpdir_prefix/$gname" if ($grpmode != $new_grpmode);
	    }
	    chmod $new_svnmode,"$svn_prefix/$gname" if ($svnmode != $new_svnmode);
        }

        $group_dir = $grpdir_prefix."/".$gname;
        # $log_dir = $group_dir."/log";
        #$cgi_dir = $group_dir."/cgi-bin";
        $ht_dir = $group_dir."/htdocs";
        $ftp_frs_group_dir = $ftp_frs_dir_prefix."/".$gname;
        $ftp_anon_group_dir = $ftp_anon_dir_prefix."/".$gname;

	if($server_is_master) {
	    # Now lets create the group's homedir.
	    # (put the SGID sticky bit on all dir so that all files
	    # in there are owned by the project group and not
	    # the user own group
	    # For some reason setting the SGID bit in mkdir doesn't work
	    # (perl bug ?) hence the chmod
	    if ( $gstatus eq 'A' && !(-e "$group_dir")) {
		
		mkdir $group_dir, 0775;
		chown $dummy_uid, $gid, ($group_dir);
		chmod 02775, ($group_dir);
	    }
	    if ( $gstatus eq 'A' && !(-e "$ht_dir")) {
		mkdir $ht_dir, 0775;
		chown $dummy_uid, $gid, ($ht_dir);
		chmod 02775, ($ht_dir);
		
		# Copy the default empty page for Web site
		# Check if a custom page exists
		$custom_homepage = $sys_custom_incdir."/en_US/others/default_page.php";
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
	    }
	}

	if($server_is_master) {
	    if ( $gstatus eq 'A' && !(-e "$ftp_anon_group_dir")) {

		# Now lets create the group's ftp homedir for anonymous ftp space
		# This one must be owned by the project gid so that all project
		# admins can work on it (upload, delete, etc...)
		mkdir $ftp_anon_group_dir, 0775;
		chown $dummy_uid, $gid, "$ftp_anon_group_dir";
	    }
	}

	if(service_available_on_server($server_is_master, $file_location, $file_server_id)) {
	    if ( $gstatus eq 'A' && !(-e "$ftp_frs_group_dir")) {
		# Now lets create the group's ftp homedir for file release space
		# (this one has limited write access to project members and read
		# read is also for project members as well (download has to go
		# through the Web for accounting and traceability purpose)
		mkdir $ftp_frs_group_dir, 0771;
		chown $dummy_uid, $gid, "$ftp_frs_group_dir";
	    }
	}

    }



print ("\n      Groups processing Results\n\n");
print "New groups       : $new_group\n";
print "Updated groups   : $up_group\n";
print "Deleted groups   : $del_group\n";

#
# Now write out the new files
#
write_array_file("/etc/passwd", @passwd_array);
write_array_file("/etc/shadow", @shadow_array);
write_array_file("/etc/group", @group_array);
write_array_file($apache_htpasswd, @htpasswd_array);
if ($winaccount_on) {
  write_array_file($smb_passwd_file, @smbpasswd_array);
  chmod 0600, "$smb_passwd_file";
}
write_array_file($cvs_root_allow_file, @cvs_root_allow_array);

if ($use_cvsnt && $server_is_master) {
  # Write cvsroot list in CVSNT config file
  open FILE,">$cvsnt_config_file";
  print FILE "# CodeX CVSROOT directory list: do not edit this list! modify $cvs_root_allow_file instead\n";
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

#
#  Deleting files older than 2 hours in var/run/log_accum that contain 'files' (they have not been deleted due to commit abort) 
#

#print("Deleting old files in /var/run/log_accum");
$TMPDIR = "/var/run/log_accum";
@old_files=`find $TMPDIR -name "*.files.*" -amin +120 `;
foreach (@old_files) {
  chomp;
  unlink $_;
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
	
	$home_dir = $homedir_prefix."/".$username;

	print("Making a User Account for: $username\n");
		
	push @passwd_array, "$username:x:$uid:$uid:$realname <$email>:$home_dir:$shell\n";
	push @shadow_array, "$username:$passwd:$date:0:99999:7:::\n";
	push @group_array, "$username:x:$uid:\n";

	# Now lets create the homedir and copy the contents of
	# /etc/skel_codex into it. The change the ownership
	unless (-d "$home_dir") {
            mkdir $home_dir, 0751;
	    if (-d "$codex_shell_skel") {
	        system("cd $codex_shell_skel; tar cf - . | (cd  $home_dir ; tar xf - )");
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
	
	print("Updating Account for: $username\n") if $verbose;
	
	$counter = 0;
	my $found   = 0;
        if (defined $passwd_hash{$uid}) {
          $found = 1;
          # Need user home dir from existing file
          ($p_username, $p_junk, $p_uid, $p_gid, $p_realname_email, $p_homedir, $p_shell) = split(":", $passwd_array[$passwd_hash{$uid}]);
          $passwd_array[$passwd_hash{$uid}]="$username:x:$uid:$uid:$realname <$email>:$p_homedir:$shell\n";
        }
        if (defined $shadow_hash{$username}) {
          ($s_username, $s_passwd, $s_date, $s_min, $s_max, $s_inact, $s_expire, $s_flag, $s_resv) = split(":", $shadow_array[$shadow_hash{$username}]);
          $shadow_array[$shadow_hash{$username}] = "$username:$passwd:$s_date:$s_min:$s_max:$s_inact:$s_expire:$s_flag:$s_resv";
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
	
  return if (!$winaccount_on);

  my $found   = 0;
  if (defined $smbpasswd_hash{$uid}) {
      $found = 1;
      my ($p_username, $p_uid, $p_win_passwd, $p_winnt_passwd,$p_account_bits,
          $p_last_set_time, $p_realname) = split(":", $smbpasswd_array[$smbpasswd_hash{$uid}]);
      $win_date = sprintf("%08X", time());
      if ($win_passwd ne $p_win_passwd) {
	$smbpasswd_array[$smbpasswd_hash{$uid}] = "$username:$uid:$win_passwd:$winnt_passwd:[U          ]:LCT-$win_date:$realname\n";
      }
    }	
  
  # if account not found then create the entry again
  add_winuser($uid, $username, $realname, $win_passwd, $winnt_passwd) unless $found;
}

sub update_httpuser {
  my ($username, $passwd) = @_;

  my ($p_username, $p_passwd);

  my $counter = 0;
  my $found   = 0;
  if (defined $htpasswd_hash{$username}) {
    $found = 1;
    ($p_username, $p_passwd) = split(":", $htpasswd_array[$htpasswd_hash{$username}]);
    if ($passwd ne $p_passwd) {
	$htpasswd_array[$htpasswd_hash{$username}] = "$username:$passwd\n";
      }
  }

  add_httpuser($username, $passwd) unless $found;
	
}


#############################
# User Deletion Function
#############################
sub delete_user {
	my $this_uid = shift(@_);
	my $this_user = shift(@_);

        if (defined $passwd_hash{$this_uid}) {
          $passwd_array[$passwd_hash{$this_uid}] = '';
        }

        if (defined $shadow_hash{$this_user}) {
          $shadow_array[$shadow_hash{$this_user}] = '';
        }


        my $counter = 0;	
	foreach (@group_array) {
		my ($groupname) = split(":", $_);
		if ($this_user eq $groupname) {
			$group_array[$counter] = '';
		}
		$counter++;
        }
	
	print("Deleting User : $this_user\n");
	system("cd $homedir_prefix ; /bin/tar -czf $tmp_dir/$this_user.tar.gz $this_user");
        chmod 0600, "$tmp_dir/$this_user.tar.gz";
	system("rm -fr $homedir_prefix/$this_user");
}

sub delete_winuser {
  my $this_uid = shift(@_);
	
  return if (!$winaccount_on);
  
  if (defined $smbpasswd_hash{$this_uid}) {
    $smbpasswd_array[$smbpasswd_hash{$this_uid}] = '';
  }
}	


sub delete_httpuser {
  my $this_user = shift(@_);

  if (defined $htpasswd_hash{$this_user}) {
    $htpasswd_array[$htpasswd_hash{$this_user}] = '';
  }
}

#############################
# User Suspension Function
#############################
sub suspend_user {
  my $this_user = shift(@_);
	
  if (defined $shadow_hash{$this_user}) {
    my ($s_username, $s_passwd, $s_date, $s_min, $s_max, $s_inact, $s_expire, $s_flag, $s_resv) = split(":", $shadow_array[$shadow_hash{$this_user}]);
    # if already suspended then give up
    if ($s_passwd =~ /^!!/) {
      return 0;
    } else {
      my $new_passwd = "!!" . $s_passwd;
      $shadow_array[$shadow_hash{$this_user}]= "$s_username:$new_passwd:$s_date:$s_min:$s_max:$s_inact:$s_expire:$s_flag:$s_resv";
    }
    return 1;
  }
}

sub suspend_winuser {
  my $this_uid = shift(@_);
	
  return if (!$winaccount_on);
  
  my $new_account_bits = "[DU         ]"; # D flag for suspended
  if (defined $smbpasswd_hash{$this_uid}) {
    ($username, $uid, $win_passwd, $winnt_passwd,$account_bits,
     $last_set_time, $realname) = split(":",$smbpasswd_array[$smbpasswd_hash{$this_uid}]);
    # if already suspended then give up
    return if ($account_bits =~ /DU/);
    $smbpasswd_array[$smbpasswd_hash{$this_uid}] = "$username:$uid:$win_passwd:$winnt_passwd:$new_account_bits:$last_set_time:$realname";
  }
}


sub suspend_httpuser {
  my $this_user = shift(@_);
  my ($username, $p_passwd);
  my $counter = 0;

  if (defined $htpasswd_hash{$this_user}) {
    ($username,$p_passwd) = split(":", $htpasswd_array[$htpasswd_hash{$this_user}]);
    $htpasswd_array[$htpasswd_hash{$username}] ="$username:!!\n";
  }
}

#############################
# Group Add Function
#############################
sub add_group {  
	my ($gid, $gname, $userlist) = @_;

	print("Making a Group for : $gname\n");
		
	push @group_array, "$gname:x:$gid:$userlist\n";

        # Add the CVS repo in the allowed root for CVS server
	push @cvs_root_allow_array, "/cvsroot/$gname\n";
	
}

#############################
# Group Update Function
#############################
sub update_group {
	my ($gid, $gname, $userlist) = @_;
	my ($p_gname, $p_junk, $p_gid, $p_userlist);
	my $modified = 0;
        my $counter = 0;

	print("Updating Group: $gname\n") if $verbose;
	
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

        print("Deleting Group: $this_group\n");
        system("cd $grpdir_prefix ; /bin/tar -czf $tmp_dir/$this_group.tar.gz $this_group");
        chmod 0600, "$tmp_dir/$this_group.tar.gz";
        system("rm -fr $grpdir_prefix/$this_group");


        # And do the same for the CVS, SVN, Files directories
        system("cd $cvs_prefix ; /bin/tar -czf $tmp_dir/$this_group-cvs.tar.gz $this_group");
        system("rm -fr $cvs_prefix/$this_group");
        chmod 0600, "$tmp_dir/$this_group-cvs.tar.gz";

        system("cd $svn_prefix ; /bin/tar -czf $tmp_dir/$this_group-svn.tar.gz $this_group");
        system("rm -fr $svn_prefix/$this_group");
        chmod 0600, "$tmp_dir/$this_group-svn.tar.gz";
}

