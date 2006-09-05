#!/usr/bin/perl

# Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
#
# Originally written by Manuel Vacelet, 2006
#
# This file is a part of CodeX.
#
# CodeX is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# CodeX is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with CodeX; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# $Id$

##
# Special note about "link in title conversion":
# This script do not handle all complex cases the following (at least are not
# taken in account):
# * missing leading </a> at the end of the link
# * not complete links such as <a href="toto">Coin</a>coin
##

use DBI;

$root_path = "../../../SF/";
require $root_path."utils/include.pl";

&db_connect;

#sub createNewService {
#
#}

sub createDocument {

}

sub createLink {
    my ($title, $link, $group_id, $parent_id, $description, $update_date, $create_date, $user_id, $rank) = @_;
 
    my $query = "INSERT INTO plugin_docman_item(parent_id, group_id, title, description, create_date, update_date, user_id, rank,item_type, link_url) VALUES (".$parent_id.", ".$group_id.", '".$folder_name."', '".$description."', ".$create_date.", ".$update_date.", ".$user_id.", 3, ".$rank.", '".$link."')";

    my $c = $dbh->prepare($query);
    $c->execute();
}

sub createFolder {
    my ($folder_name, $group_id, $parent_id, $rank) = @_;
 
    my $query = "INSERT INTO plugin_docman_item(parent_id, title, group_id, item_type, rank) VALUES (".$parent_id.", '".$folder_name."', ".$group_id.", 1, ".$rank.")";

    my $c = $dbh->prepare($query);
    $c->execute();

    return $dbh->{'mysql_insertid'};
}

sub htmlspecialchars {

}

sub unconvert_htmlspecialchars {
    my ($string) = @_;
#    $string =~ s/&nbsp;/ /gi;
    $string =~ s/&gt;/>/gi;
    $string =~ s/&lt;/</gi;
    $string =~ s/&amp;/&/gi;
    $string =~ s/&quot;//gi;
    $string =~ s/&\#039;//gi;
    return $string;
}



sub itemFactory {
    my ($group_id, $parent_id, $title, $updatedate, $createdate, $created_by, $description, $filename, $filesize, $filetype, $rank) = @_;
    
    $oldT = $title;
    $title = unconvert_htmlspecialchars($title);

    if($title =~ /http/i) {
	print "**$oldT\n";
	## Is a link or and wiki page

	#$title =~ m/href=(.*)&gt;(.*)&lt;\/a&gt;$/i;
	$title =~ m/href=([^>]*)>(.*)<\/a>$/i;
	my $doc_link = $1;
	my $doc_name = $2;
	

	print "->".$doc_name."\n";
	print "->".$doc_link."\n\n";
    }
    else {
	## Is a file (embedded or not)
	#print "**$title\n";
    }

}

sub convertDocDataInItems {
    my ($doc_group, $item_id, $group_id) = @_;

    my $query = "SELECT title, updatedate, createdate, created_by, description, filename, filesize, filetype, rank FROM doc_data WHERE doc_group=".$doc_group;
    $c = $dbh->prepare($query);
    $c->execute();
    while (my ($title, $updatedate, $createdate, $created_by, $description, $filename, $filesize, $filetype, $rank) = $c->fetchrow()) {
	#createFolder($title, $group_id, $item_id, $rank);
	itemFactory($group_id, $item_id, $title, $updatedate, $createdate, $created_by, $description, $filename, $filesize, $filetype, $rank);
    }
}

sub convertDocGroupsInFolders {

    my ($query, $c);


    $query = "SELECT doc_group,groupname,group_rank FROM doc_groups";

    $c = $dbh->prepare($query);
    $c->execute();
    while (my ($doc_group,$groupname,$group_rank) = $c->fetchrow()) {
	#my $newId = createFolder($groupname, 101, 0, $group_rank);
	my $newId = 0;
	convertDocDataInItems($doc_group, $newId, 101);
    }
}

convertDocGroupsInFolders();

1;
