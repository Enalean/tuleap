#!/usr/bin/perl -w
#
# SourceForge: Breaking Down the Barriers to Open Source Development
# Copyright 1999-2000 (c) The SourceForge Crew
# http://sourceforge.net
#
# $Id$
#
use DBI;
use Sys::Hostname;
use POSIX qw(strftime);

&open_log_file;

# All of the files that we will be creating
my @file_array = ("user_dump", "group_dump", "ssh_dump", "list_dump", "alias_dump", "httpd_conf", "aliases", "dns_sourceforge_net");

# Check to make sure that the environment is clean
if (! -d $file_dir) {
	&logme("The file directory doesn't exist: $file_dir");
	exit 1;
}

foreach(@file_array) {
	if (-f $file_dir.$_) {
		&logme("Another Dump File Exists (Overwriting): $_");
	}
}

my ($foo, $bar, $x);
	
# open up database include file and get the database variables
open(FILE, $db_include) || die "Can't open $db_include: $!\n";
while ($x = <FILE>) {
	($foo, $bar) = split /=/, $x;
	if ($foo) { eval $x; }
}
close(FILE);

# connect to the database
$dbh ||= DBI->connect("DBI:mysql:$sys_dbname:$sys_dbhost", "$sys_dbuser", "$sys_dbpasswd");


print("Dumping Table Data\n");

# Okay lets dump and configure all the tables now.

my ($query, $c, @tmp_array);

###################################
# First the User Information.
###################################
print("Dumping User Data: ");

$query = "select unix_uid, unix_status, user_name, shell, unix_pw, realname from user where unix_status != \"N\"";
$c = $dbh->prepare($query);
$c->execute();

while(my ($id, $status, $username, $shell, $passwd, $realname) = $c->fetchrow()) {
	$username =~ s/://g;
	push @tmp_array, "$id:$status:$username:$shell:$passwd:$realname\n";
}

&done("user_dump", @tmp_array);
undef @tmp_array;


###################################
# Now Dump the Group Information.
###################################
print("Dumping Group Data: ");

$query = "select group_id,unix_group_name,status from groups";
$c = $dbh->prepare($query);
$c->execute();

while(my ($group_id, $group_name, $status) = $c->fetchrow()) {
	$new_query = "select user.user_name AS user_name FROM user,user_group WHERE user.user_id=user_group.user_id AND group_id=$group_id";
	$d = $dbh->prepare($new_query);
	$d->execute();

	$user_list = "";
	while($user_name = $d->fetchrow()) {
		$user_list .= "$user_name,";
	}

	$group_list = "$group_name:$status:$group_id:$user_list\n";
	$group_list =~ s/,$//;	# regex out the last comma on the line

	push @tmp_array, $group_list;
}

&done("group_dump", @tmp_array);
undef @tmp_array;

###################################
# Dump the SSH authorized_keys Data
###################################
print("Dumping SSH Data: ");

$query = "SELECT user_name,authorized_keys FROM user WHERE authorized_keys != \"\"";
$c = $dbh->prepare($query);
$c->execute();

while(my ($username, $ssh_key) = $c->fetchrow()) {
	$ssh_key =~ s/://g;
	push @tmp_array, "$username:$ssh_key\n";
}

# Now write out the files
&done("ssh_dump", @tmp_array);
undef @tmp_array;


###################################
# Dump the Mailing list Information
###################################
print("Dumping Mailing List Data: ");

$query = "SELECT user.user_name,mail_group_list.list_name,mail_group_list.password,mail_group_list.status FROM mail_group_list,user WHERE mail_group_list.list_admin=user.user_id";
$c = $dbh->prepare($query);
$c->execute();

while(my ($list_name, $list_admin, $password, $status) = $c->fetchrow()) {
	push @tmp_array, "$list_name:$list_admin:$password:$status\n";
}

&done("list_dump", @tmp_array);
undef @tmp_array;

###################################
# Apache Dump and configuration
###################################
print("Dumping httpd.conf Data: ");
$query = "SELECT http_domain,unix_group_name,group_name,status FROM groups WHERE unix_box='shell1' AND http_domain LIKE '%.%'";
$c = $dbh->prepare($query);
$c->execute();

@tmp_array = open_array_file("./zones/httpd.conf.zone");

while(my ($http_domain,$unix_group_name,$group_name,$status) = $c->fetchrow()) {
	if ($status eq "A") {
		push @tmp_array, "\n\n### Host entries for: $group_name\n\n";
		push @tmp_array, "<Directory \"$grpdir_prefix$unix_group_name/htdocs\">\n";
		push @tmp_array, "    AllowOverride AuthConfig FileInfo\n";
		push @tmp_array, "    Options Indexes Includes\n";
		push @tmp_array, "    Order allow,deny\n";
		push @tmp_array, "    Allow from all\n";
		push @tmp_array, "</Directory>\n";
		push @tmp_array, "<Directory \"$grpdir_prefix$unix_group_name/cgi-bin\">\n";
		push @tmp_array, "    AllowOverride AuthConfig FileInfo\n";
		push @tmp_array, "    Options ExecCGI\n";
		push @tmp_array, "    Order allow,deny\n";
		push @tmp_array, "    Allow from all\n";
		push @tmp_array, "</Directory>\n";
		push @tmp_array, "<VirtualHost 192.168.4.52>\n";
		push @tmp_array, "    DocumentRoot \"$grpdir_prefix$unix_group_name/htdocs/\"\n";
		push @tmp_array, "    CustomLog $grpdir_prefix$unix_group_name/log/combined_log combined\n";
		push @tmp_array, "    ScriptAlias /cgi-bin/ \"$grpdir_prefix$unix_group_name/cgi-bin/\"\n";
		push @tmp_array, "    Servername $http_domain\n";
		push @tmp_array, "</VirtualHost>\n";
	}
}

&done("httpd_conf", @tmp_array);
undef @tmp_array;

###################################
# Dump Project Mail Aliases (virtusertable)
###################################
print("Dumping Project Mail Alias Data: ");
$query = "SELECT mailaliases.user_name,groups.http_domain,mailaliases.email_forward FROM mailaliases,groups WHERE mailaliases.group_id=groups.group_id";

$c = $dbh->prepare($query);
$c->execute();

while(($username,$domainname,$userlist) = $c->fetchrow()) {
        push @tmp_array, "$username:$domainname:$userlist\n";
}

# now dump all the normal user stuff
$query = "SELECT user_name,email FROM user WHERE status = \"A\" AND email != \"\"";
$c = $dbh->prepare($query);
$c->execute();
while(my ($username, $email) = $c->fetchrow()) {
	push @tmp_array, "$username:users.sourceforge.net:$username\n";
}

&done("alias_dump", @tmp_array);
undef @tmp_array;

###################################
# Dump User Mail Aliases (/etc/aliases)
###################################
print("Dumping /etc/aliases Data: ");

@tmp_array = open_array_file("./zones/aliases.zone");

# First lets Dump the Mailing List Info
push @tmp_array, "\n\n### Begin Mailing List Aliases ###\n\n";

$query = "SELECT list_name from mail_group_list";
$c = $dbh->prepare($query);
$c->execute();
while(my ($list_name) = $c->fetchrow()) {
	push @tmp_array, sprintf("%-60s%-10s","$list_name\@lists.sourceforge.net:", "\"|/usr/local/mailman/mail/wrapper post $list_name\"\n");
	push @tmp_array, sprintf("%-60s%-10s","$list_name-admin\@lists.sourceforge.net:", "\"|/usr/local/mailman/mail/wrapper mailowner $list_name\"\n");
	push @tmp_array, sprintf("%-60s%-10s","$list_name-request\@lists.sourceforge.net:", "\"|/usr/local/mailman/mail/wrapper mailcmd $list_name\"\n");
}

&done("aliases", @tmp_array);
undef @tmp_array;

###################################
# Dump DNS Information
###################################
print("Dumping DNS Zone File Data: ");

@tmp_array = open_array_file("./zones/dns.zone");

# Update the Serial Number
$date_line = $tmp_array[1];
$date_line =~ s/\t\t\t/\t/;

my ($blah,$date_str,$comments) = split("	", $date_line);
$date = $date_str;

my $serial = substr($date, 8, 2);
my $old_day = substr($date, 6, 2);

$serial++;

$now_string = strftime "%Y%m%d", localtime;
$new_day = substr($now_string, 6, 1);

if ($old_day != $new_day) { $serial = "01"; }

$new_serial = $now_string.$serial;

$tmp_array[1] = "		$blah	$new_serial	$comments";

&write_array_file("./zones/dns.zone", @tmp_array); # write the new serial out the zone file

$query = "SELECT http_domain,unix_group_name,group_name,unix_box FROM groups WHERE http_domain LIKE '%.%' AND status = 'A'";
$c = $dbh->prepare($query);
$c->execute();

while(my ($http_domain,$unix_group_name,$group_name,$unix_box) = $c->fetchrow()) {
	($foo, $foo, $foo, $foo, @addrs) = gethostbyname("$unix_box.sourceforge.net");
	@blah = unpack('C4', $addrs[0]);
	$ip = join(".", @blah);

	push @tmp_array, sprintf("%-24s%-16s",$unix_group_name,"IN\tA\t" . "$ip\n");
	push @tmp_array, sprintf("%-24s%-28s","", "IN\tMX\t" . "mail1.sourceforge.net.\n");
	push @tmp_array, sprintf("%-24s%-30s","cvs.".$unix_group_name,"IN\tCNAME\t" . "cvs1.sourceforge.net."."\n\n");
}

&done("dns_sourceforge_net", @tmp_array);
undef @tmp_array;

$dbh->disconnect();

sub done {	# Done Function for db_parse.pl
	my ($file_name, @file_array) = @_;
	
	write_array_file($file_dir.$file_name, @file_array);
	print("Done.\n");
}

sub open_array_file {	# File open function, spews the entire file to an array.
        my $filename = shift(@_);

	# Now read in the file as a big array
        open (FD, $filename) || die "Can't open $filename: $!.\n";
        @tmp_array = <FD>;
        close(FD);
        
        return @tmp_array;
}       


sub write_array_file {	# File write function.
        my ($file_name, @file_array) = @_;

	# Write this array out to $filename
        open(FD, ">$file_name") || die "Can't open $file_name: $!.\n";
        foreach (@file_array) { 
                if ($_ ne '') { 
                        print FD;
                }       
        }       
        close(FD);
}      
