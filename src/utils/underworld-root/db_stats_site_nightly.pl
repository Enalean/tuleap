#!/usr/bin/perl 
#
# 
#

use DBI;
use Time::Local;
use POSIX qw( strftime );

require("../include.pl");
&db_connect();

my ($sql, $rel, $day_begin, $day_end, $mon, $week, $day);
my $verbose = 1;


if ( $ARGV[0] && $ARGV[1] && $ARGV[2] ) {

        $day_begin = timegm( 0, 0, 0, $ARGV[2], $ARGV[1] - 1, $ARGV[0] - 1900 );
        $day_end = timegm( 0, 0, 0, (gmtime( $day_begin + 86400 ))[3,4,5] );

} else {

           ## Start at midnight last night.
        $day_end = timegm( 0, 0, 0, (gmtime( time() ))[3,4,5] );
           ## go until midnight yesterday.
        $day_begin = timegm( 0, 0, 0, (gmtime( time() - 86400 ))[3,4,5] );

}


##
## EOF
##
