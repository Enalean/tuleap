#!/usr/bin/perl
#
# $Id$
#
use DBI;

require("../include.pl");  # Include all the predefined functions

&db_connect;

# array vor category counts;
my @cat;

# gather all releases 
my $query = "SELECT group_id FROM groups WHERE is_public=1 AND (status='A')";
my $grp = $dbh->prepare($query);
$grp->execute();

# recursive sub for each node

sub incnode {
	local ($category) = @_;

	#increment this category
	@onecat[$category] = 1;
	
	#do the same for all parent categories only if not at root yet
	if ($category <= 0) {
		return;
	}

	my $query = "SELECT parent FROM trove_cat WHERE trove_cat_id=$category";
	my $catparent = $dbh->prepare($query);
	$catparent->execute();

	while (my ($parent) = $catparent->fetchrow()) {
		&incnode($parent);
	}
}

# for each release check all parents, all the way to the top
while(my ($group_id) = $grp->fetchrow()) {
	#clear onecat
	@onecat = ();

	# find all categories for group
	my $query = "SELECT trove_cat_id FROM trove_group_link WHERE group_id=$group_id";
	my $grpcat = $dbh->prepare($query);
	$grpcat->execute();

	# for each category
	while (my ($category_id) = $grpcat->fetchrow()) {
		&incnode($category_id);
	}

	# add onecat entries to total cat
	for ($i=0;$i<@onecat;$i++) {	
		@cat[$i] += @onecat[$i];
	}
}

# output results, write to db

my $query = "DROP table IF EXISTS temp_trove_treesums";
my $queryres = $dbh->prepare($query);
$queryres->execute();

my $query = "CREATE TABLE temp_trove_treesums ("
	."trove_treesums_id INT NOT NULL auto_increment PRIMARY KEY,"
	."trove_cat_id INT NOT NULL,"
	."limit_1 INT NOT NULL,"
	."subprojects INT NOT NULL"
	.")";
my $queryres = $dbh->prepare($query);
$queryres->execute();

for ($i=0;$i<@cat;$i++) {
	if (@cat[$i]) {
		my $query = "INSERT INTO temp_trove_treesums (trove_cat_id,subprojects) "
			."VALUES (".$i.",".@cat[$i].")";
		my $update = $dbh->prepare($query);
		$update->execute();
	}
	$atleastsomesuccess = 1;
}

if ($atleastsomesuccess) {
	my $query = "DROP table IF EXISTS trove_treesums";
	my $queryres = $dbh->prepare($query);
	$queryres->execute();
	my $query = "ALTER table temp_trove_treesums RENAME AS trove_treesums";
	my $queryres = $dbh->prepare($query);
	$queryres->execute();
}
