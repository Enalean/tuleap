# correct dates in DB in group_svn_full_history where months and days do
# not contain the leading 0. The date format is %Y%m%d (e.g. 20041024)

#!/usr/bin/perl

use DBI;

$root_path = "../../";
require $root_path."utils/include.pl";

&db_connect;


sub correct_svn_date {
	my ($query, $c, $q, $d, $correct_day);

	$query = "SELECT group_id, user_id, day FROM group_svn_full_history";

	$c = $dbh->prepare($query);
    	$c->execute();
    	while (my ($group_id, $user_id, $day) = $c->fetchrow()) {
		
		#parse and correct the day
		$correct_day = $day;
		
		# we are in 2004 and all dates are after january
		$correct_day =~ s/^2004([2-9])(.)$/20040${1}0$2/;
		$correct_day =~ s/^2004([2-9])(..)$/20040$1$2/;
		$correct_day =~ s/^2004(1.)(.)$/2004${1}0$2/;

		# we are in 2005 and all dates are before october
		$correct_day =~ s/^2005(.)(.)$/20050${1}0$2/;
		$correct_day =~ s/^2005(.)(..)$/20050$1$2/;

		#update DB
		if ($correct_day != $day) {
			$q = "UPDATE group_svn_full_history SET day=$correct_day WHERE group_id=$group_id AND user_id=$user_id AND day=$day";
			print $q."\n";
			$d = $dbh->prepare($q);
  			$d->execute();
		}
	}
}

correct_svn_date();

1;
