#!/usr/bin/perl
#
#
#
# include.pl - Include file for all the perl scripts that contains reusable functions
#

use JSON;

#Codendi is full utf-8
$ENV{LANG} = 'en_US.UTF-8';

##############################
# Global Variables
##############################
$date           =       int(time()/3600/24);    # Get the number of days since 1/1/1970 for /etc/shadow

load_local_config();

1;

##############################
# Local Configuration Load
##############################

sub load_local_config {
    $perl_scalar = decode_json(`/usr/bin/tuleap config-dump`);
    while (my ($key, $value) = each($perl_scalar)) {
        $$key=$value;
    }
}

##############################
# Database Connect Functions
##############################
sub db_connect {

	# connect to the database
	my $dbopt = '';
	if ($sys_enablessl) {
        # RHEL/CENTOS7 version of perl cannot verify SSL Cert issuer. Moreover perl package is affected by [1] and there
        # are no evidences that this corresponding fix was backported.
        # [1] https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2017-10789
	    $dbopt = ';mysql_ssl=1;mysql_ssl_verify_server_cert=0';
        if ($sys_db_ssl_ca && -f $sys_db_ssl_ca) {
            $dbopt .= ';mysql_ssl_ca_file='.$sys_db_ssl_ca;
        }
	}
    if ($sys_dbport) {
        $dbopt .= ';port='.$sys_dbport;
    }
	$dbh ||= DBI->connect("DBI:mysql:$sys_dbname:$sys_dbhost$dbopt", "$sys_dbuser", "$sys_dbpasswd");

        #Connect with UTF-8 encoding
        $query = "SET NAMES 'utf8'";
        $sth = $dbh->prepare($query);
        $sth->execute();
}


#############################
# Get Codendi apache user from local.inc
#############################
sub get_codendi_user {

  return $sys_http_user;

  #open(APCONF, $apache_conf) or return;
  #while (<APCONF>) {
  #  return $1 if /^\s*User\s+(.*)\s*/;
  #}

}

1;
