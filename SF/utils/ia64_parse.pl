#!/usr/bin/perl
#
# $Id$
#
# new_parse.pl - new script to parse out the database dumps and create/update/delete user
#		 accounts on the client machines
use Sys::Hostname;

$hostname = hostname();

require("include.pl");  # Include all the predefined functions and variables

my $user_file = $file_dir . "ia64_dump";
my ($uid, $status, $username, $shell, $passwd, $realname);
my ($gname, $gstatus, $gid, $userlist);

# Open up all the files that we need.
@userdump_array = open_array_file($user_file);
@passwd_array = open_array_file("/etc/passwd");
@shadow_array = open_array_file("/etc/shadow");

#
# Loop through @userdump_array and deal w/ users.
#
print ("\n\n	Processing Users\n\n");
while ($ln = pop(@userdump_array)) {
	chop($ln);
	($uid, $status, $username, $shell, $passwd, $realname) = split(":", $ln);

	$uid += $uid_add;

	$username =~ tr/A-Z/a-z/;
	
	$user_exists = getpwnam($username);
	
	if ($status eq 'A' && $user_exists) {
		update_user($uid, $username, $realname, $shell, $passwd);
	
	} elsif ($status eq 'A' && !$user_exists) {
		add_user($uid, $username, $realname, $shell, $passwd);
	
	} elsif ($status eq 'D' && $user_exists) {
		delete_user($username);
	
	} elsif ($status eq 'D' && !$user_exists) {
		print("Error trying to delete user: $username\n");
		
	} elsif ($status eq 'S' && $user_exists) {
		suspend_user($username);
		
	} elsif ($status eq 'S' && !$user_exists) {
		print("Error trying to suspend user: $username\n");
		
	} else {
		print("Unknown Status Flag: $username\n");
	}
}

#
# Now write out the new files
#
write_array_file("/etc/passwd", @passwd_array);
write_array_file("/etc/shadow", @shadow_array);


###############################################
# Begin functions
###############################################

#############################
# User Add Function
#############################
sub add_user {  
	my ($uid, $username, $realname, $shell, $passwd) = @_;
	my $skel_array = ();
	
	$home_dir = $homedir_prefix.$username;

	print("Making a User Account for : $username\n");
		
	push @passwd_array, "$username:x:$uid:100:$realname:$home_dir:$shell\n";
	push @shadow_array, "$username:$passwd:$date:0:99999:7:::\n";
	
	# Now lets create the homedir and copy the contents of /etc/skel into it.
	mkdir $home_dir, 0751;
	
       chown $uid, $uid, $home_dir;
}

#############################
# User Add Function
#############################
sub update_user {
	my ($uid, $username, $realname, $shell, $passwd) = @_;
	my ($p_uid, $p_junk, $p_uid, $p_gid, $p_realname, $p_homedir, $p_shell);
	my ($s_username, $s_passwd, $s_date, $s_min, $s_max, $s_inact, $s_expire, $s_flag, $s_resv, $counter);
	
	print("Updating Account for: $username\n");
	
	foreach (@passwd_array) {
		($p_uid, $p_junk, $p_uid, $p_gid, $p_realname, $p_homedir, $p_shell) = split(":", $_);
		
		if ($uid == $p_uid) {
			if ($realname ne $p_realname) {
				$passwd_array[$counter] = "$username:x:$uid:100:$realname:$p_homedir:$shell\n";
			} elsif ($shell ne $t_shell) {
				$passwd_array[$counter] = "$username:x:$uid:100:$p_realname:$p_homedir:$p_shell";
			}
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
		}
		$counter++;
	}
}

#############################
# User Deletion Function
#############################
sub delete_user {
	my ($username, $junk, $uid, $gid, $realname, $homedir, $shell, $counter);
	my $this_user = shift(@_);
	
	foreach (@passwd_array) {
		($username, $junk, $uid, $gid, $realname, $homedir, $shell) = split(":", $_);
		if ($this_user eq $username) {
			$passwd_array[$counter] = '';
		}
		$counter++;
	}
	
	print("Deleting User : $this_user\n");
	system("cd $homedir_prefix ; /bin/tar -czf $tar_dir/$username.tar.gz $username");
	system("rm -fr $homedir_prefix/$username");
}

#############################
# User Suspension Function
#############################
sub suspend_user {
	my $this_user = shift(@_);
	my ($s_username, $s_passwd, $s_date, $s_min, $s_max, $s_inact, $s_expire, $s_flag, $s_resv, $counter);
	
	my $new_pass = "!!" . $s_passwd;
	
	foreach (@shadow_array) {
		($s_username, $s_passwd, $s_date, $s_min, $s_max, $s_inact, $s_expire, $s_flag, $s_resv) = split(":", $_);
		if ($username eq $s_username) {
		       $shadow_array[$counter] = "$s_username:$new_pass:$s_date:$s_min:$s_max:$s_inact:$s_expire:$s_flag:$s_resv";
		}
		$counter++;
