#!/usr/bin/perl
#
# $Id$
# new_aliases.pl - Updates virtusertable and /etc/sendmail.cw on mail1

use DBI;

require("../include.pl");  # Include all the predefined functions

&db_connect;

my $query = "SELECT mailaliases.user_name,groups.http_domain,mailaliases.email_forward FROM mailaliases,groups WHERE mailaliases.group_id=groups.group_id";
my ($c, $username, $domainname, $userlist, $email);

my $alias_array = ();

$c = $dbh->prepare($query);
$c->execute();

while(($username,$domainname,$userlist) = $c->fetchrow()) {
	push @alias_array, "$username:$domainname:$userlist\n";
}

$query = "SELECT user_name,email FROM user WHERE status = \"A\"";

push @alias_array, "*:eterm.org:mej\@valinux.com\n";
push @alias_array, "log4j-announce:log4j.org:log4j-announce\@lists.sourceforge.net\n";
push @alias_array, "log4j-public:log4j.org:log4j-public\@lists.sourceforge.net\n";
push @alias_array, "log4j-dev:log4j.org:log4j-dev\@lists.sourceforge.net\n";
push @alias_array, "plplot:plplot.org:plplot-general\@lists.sourceforge.net\n";
push @alias_array, "plplot-core:plplot.org:plplot-core\@lists.sourceforge.net\n";
push @alias_array, "plplot-cvs:plplot.org:plplot-cvs\@lists.sourceforge.net\n";




$c = $dbh->prepare($query);
$c->execute();
while(($username, $email) = $c->fetchrow()) {
        if ($email) {
                if (!($admin_list =~ /.*$username*./)) {
			push @alias_array, "$username:users.sourceforge.net:$email\n";
                }
        }
}


write_array_file($file_dir."alias_dump", @alias_array);
