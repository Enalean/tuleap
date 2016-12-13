#!/usr/bin/perl
#
# 
#
use DBI;

require("../include.pl");  # Include all the predefined functions

&db_connect;

####################################################################
#get times

$oneweekago = time()-(3600*7*24);

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = gmtime($oneweekago);

if ($mday < 10) {
    $mday = "0$mday";
}
$month = ($mon + 1);

if ($month < 10) {
    $month = "0$month";
}

$year = ($year + 1900);

$oneweekago_fmt = $year . $month . $mday;

#####################################################################
################################### TOP DOWNLOADS

# get all groups, and group_names
my $query = "SELECT group_id,group_name FROM groups WHERE type=1 AND status='A' AND access != 'private' AND type='1'";
my $rel = $dbh->prepare($query);
$rel->execute();
while(my ($group_id,$group_name) = $rel->fetchrow()) {
	$top[$group_id][0] = $group_name;
	# hacked method to get last group. dbi sucks
	if ($group_id>$max_group_id) {
		$max_group_id = $group_id;
	}
}

# get old top info
my $query = "SELECT group_id,rank_downloads_all,rank_downloads_week,rank_userrank,rank_forumposts_week FROM top_group";
my $rel = $dbh->prepare($query);
$rel->execute();
while(my ($group_id,$downloads_all,$downloads_week,$userrank,$forumposts_week,)
	= $rel->fetchrow()) {
	$top[$group_id][1] = $downloads_all;
	$top[$group_id][2] = $downloads_week;
	$top[$group_id][3] = $userrank;
	$top[$group_id][4] = $forumposts_week;
}

# get current download counts 
my $query = "SELECT group_id,downloads AS count FROM frs_dlstats_grouptotal_agg "
	. "GROUP BY group_id ORDER BY count DESC";
my $rel = $dbh->prepare($query);
$rel->execute();
$currentrank = 1;
while(my ($group_id,$count) = $rel->fetchrow()) {
	$top[$group_id][5] = $count;
	$top[$group_id][6] = $currentrank;
	$currentrank++;
}

# get current weekly download counts 
my $query = "SELECT group_id,SUM(downloads) AS count FROM frs_dlstats_group_agg "
	. "WHERE ( day >= $oneweekago_fmt ) "
	. "GROUP BY group_id ORDER BY count DESC";
my $rel = $dbh->prepare($query);
$rel->execute();
$currentrank = 1;
while(my ($group_id,$count) = $rel->fetchrow()) {
	$top[$group_id][7] = $count;
	$top[$group_id][8] = $currentrank;
	$currentrank++;
}

# get forumposts_week stats
my $query = "SELECT forum_group_list.group_id AS group_id,count(*) AS count FROM "
	."forum,forum_group_list WHERE forum.group_forum_id=forum_group_list.group_forum_id "
	."AND forum_group_list.group_id>0 GROUP BY forum_group_list.group_id ORDER BY count DESC";
my $rel = $dbh->prepare($query);
$rel->execute();
$currentrank = 1;
while(my ($group_id,$count) = $rel->fetchrow()) {
	$top[$group_id][12] = $count;
	$top[$group_id][13] = $currentrank;
	$currentrank++;
}


#
#
#    another really bad way of doing this.....
#    this should be re-written to insert into a tmp table, then swap the temp
#    table in for the old real one
#
#


# store new data
for ($i=1;$i<$max_group_id;$i++) {
	#doing this one at a time so that there is no time when there is any more than one entry that isn't there
	my $query = "DELETE FROM top_group WHERE group_id=$i";
	my $rel = $dbh->prepare($query);
	$rel->execute();

	my $query = "INSERT INTO top_group (group_id,group_name,downloads_all,"
		."rank_downloads_all,rank_downloads_all_old,downloads_week,"
		."rank_downloads_week,rank_downloads_week_old,userrank,rank_userrank,"
		."rank_userrank_old,forumposts_week,rank_forumposts_week,"
		."rank_forumposts_week_old) VALUES "
		."('$i',".$dbh->quote($top[$i][0]).",'$top[$i][5]','$top[$i][6]','$top[$i][1]',"
		."'$top[$i][7]','$top[$i][8]','$top[$i][2]',"
		."'0','0','$top[$i][3]','$top[$i][12]','$top[$i][13]','$top[$i][4]')";
	my $rel = $dbh->prepare($query);
	$rel->execute();

	print "Group ID $i: $top[$i][0], $top[$i][1], $top[$i][2], $top[$i][3], $top[$i][4], "
		."$top[$i][5], $top[$i][6]\n";
}

