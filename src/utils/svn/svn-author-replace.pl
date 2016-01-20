#!/usr/bin/perl
#
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2010. All rights reserved
#
# This file is a part of Codendi.
#
# Codendi is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Codendi is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Codendi. If not, see <http://www.gnu.org/licenses/>.
#

#
# NOTICE : this script requires SVN::Dump $> cpan install SVN::Dump
#


#use strict;
#use warnings;
use Getopt::Std;
require SVN::Dump;
#require Data::Dumper;

#arg parsing
my %options = ();
getopts('u:f:', \%options);

my $user_map_file       = '';
my $svn_dump_file       = '';
$user_map_file          = $options{u};
$svn_dump_file          = $options{f};

if ( ! -r $user_map_file ) {
    die('User map file not found or not readable');
}
if ( ! -r $svn_dump_file ) {
    die('SVN dump file not found or not readable');
}


print STDERR '[INFO] Loading user map ...'."\n";
# faire un array des users.
my %user_map = ();
open(USER_MAP, '<'.$user_map_file ) or die('Unable to open user map file');
while ( <USER_MAP> ) {
    my($old_user, $new_user) = split('=', $_);
    $old_user   = &trim($old_user);
    $new_user   = &trim($new_user);
    $user_map{$old_user} = $new_user;  
}
close USER_MAP;

#process dump
my $records;
my $dump_reader = SVN::Dump->new( {file => $svn_dump_file} );
while( $record = $dump_reader->next_record() ) {
	if( $record->type() == 'revision' ) {
		if ( $record->has_prop() ) {
			my $prop   = $record->get_property_block();  
			my $author = $prop->get('svn:author');
			if ( $author ne '' ) {
				my $new_author = '';
                                #getting default new author name
				if ( exists $user_map{default} ) {
					$new_author = $user_map{default};
				}
                                #getting new author name retrieved in user map file
				if ( exists $user_map{$author} ) {
					$new_author = $user_map{$author};
				}
				$new_author = &trim($new_author);
				if ( $new_author ne '' ) {
					$prop->set('svn:author', $new_author);
					$record->update_headers();
					print STDERR '[INFO]'.$new_author." replaces ".$author."\n";
				} 
			}
		}
	}
	print $record->as_string();
}

sub trim($)
{
	my $string = shift;
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	return $string;
}

