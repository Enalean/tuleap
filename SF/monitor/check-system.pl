#!/usr/bin/perl -w
#
# Perl Script to Check the SystemDaemon to see if things are
# doing okay..
#
# $Id$
#

use IO::Socket;
use Fcntl;
use strict;

my ($problem, $host, $port, $pagemsg);

$host = 'monitor.sourceforge.net';
$port = '10000';
#$errormsg = '';

my @pass1 = &check_monitor;
my @pass2 = &check_monitor;

# now run through the two and see if there are any
# matching results.  If so we need to send a page.
foreach (@pass1) {
	# it was in both.. push it into $pagemsg
	if (grep("/$_/", @pass2)) {
		$pagemsg .= "$_\n";
	}
}		

if ($pagemsg) {
	 system("echo \"$pagemsg\" | mail admin-pager\@sourceforge.net -s SF-ALERT");
}

sub check_monitor {
	my ($sock, $bigbuf, $buf, $time, $hostname, $garbage, $service, @errormsg, @response);

	$sock = IO::Socket::INET->new(PeerAddr => $host,
                                      PeerPort => $port,
                                      Proto    => 'tcp',
                                      Timeout  => 2,
                                      Type     => SOCK_STREAM() );

	# Ooops.. we weren't able to open a socket to the monitor server.
	# something bad happened.. lets send a page and exit.
	if (!$sock) {
		system('echo "Monitor Fragged!" | mail admin-pager\@sourceforge.net -s SF-ALERT');
		die;
	}

	# set the socket to be nonblocking.
	fcntl($sock, F_SETFL(), fcntl($sock, F_GETFL(), 0) | O_NONBLOCK()) || die "Unable to make socket non-blocking: $!";

	$time = time();

	# read from the socket for 10 seconds.
	while ($time+10 > time()) {
		$sock->recv($buf, 2048, 0);
		$bigbuf .= $buf;
	}

	$sock->close();

	@response = split("\n", $bigbuf);

	# lets see what we got back...
	foreach (@response) {
		# uh-oh.. something failed.. let see what it was..
		if (/FAILED/) {
			($garbage, $service, $garbage, $hostname) = split(' ', $_);
		
#			$errormsg .= "$hostname $service\n";
			push @errormsg, "$hostname $service";
		}
	}

	return @errormsg;
}
