#!/usr/bin/perl
#
# $Id$
#
use DBI;
use POSIX qw(strftime);

require("../include.pl");  # Include all the predefined functions


&db_connect;



#
# grab Table information
#
my $query = "SELECT http_domain,unix_group_name,group_name,unix_box FROM groups WHERE http_domain LIKE '%.%' AND status = 'A'";
my $c = $dbh->prepare($query);
$c->execute();

my $warn_noip=0;

while(my ($http_domain,$unix_group_name,$group_name,$unix_box) = $c->fetchrow()) {

	($name, $aliases, $addrtype, $length, @addrs) = gethostbyname("$unix_box.$sys_default_domain");
	@blah = unpack('C4', $addrs[0]);
	$ip = join(".", @blah);
        if ($ip eq "") {
          # DNS not yet configured? Display warning and use sys_default_domain IP address
          # Otherwise, Apache won't run.
          if (!$warn_noip) {
            print "WARNING: No IP address for $unix_box.$sys_default_domain (and possibly others). Using address from $sys_default_domain.\n";
            $warn_noip=1;
          }
          ($name, $aliases, $addrtype, $length, @addrs) = gethostbyname("$sys_default_domain");
          @blah = unpack('C4', $addrs[0]);
          $ip = join(".", @blah);
        }

        # Replace double quotes by single quotes in project name.
        $group_name=~s/\"/\'/g;

	# LJ Custom log used to be on a per project basis but is now
	# in one single file
	#    "  CustomLog /home/groups/$unix_group_name/log/combined_log combined\n",
	# if the HTTP domain given in CodeX is a customized one then use it 
	# as the Server name and create an alias for projectname.codex.xerox.com
	# Note: the DNS entry for the customized HTTP domain must exist somewhere
	# in some DNS server in the Corp.
	$http_domain =~ tr/A-Z/a-z/;
	$codex_domain = "$unix_group_name.$sys_default_domain";
	if ($http_domain ne $codex_domain)
	  {
	    $server_name = "  ServerName $http_domain\n";
	    $server_alias = "  ServerAlias $codex_domain\n";
	  }
	else
	  {
	    $server_name = "  ServerName $codex_domain\n";
	    $server_alias = "";
	  }

        # Apache 2.x syntax

        # Determine whether the virtual host can be accessed through
        # HTTP and/or HTTPS
        # Name-based Virtual hosts are incompatible with HTTPS, so only use $ip:80 for now.
        # (used to be $vhost = "$ip:80 $ip:443";)
        # HTTPS and virtualhosts are compatible with IP-based vhosts.

        # Project Virtual Web site
        push @apache_zone,
          ( "<VirtualHost $ip:80>\n",
	      "$server_name",
	      "$server_alias",
	      "  SuexecUserGroup dummy $unix_group_name\n",
	      "  DocumentRoot $grpdir_prefix/$unix_group_name/htdocs/\n",
              "  php_admin_value open_basedir \"$grpdir_prefix/$unix_group_name/htdocs\"\n",
              "  php_admin_value include_path \"$grpdir_prefix/$unix_group_name/htdocs/\"\n",
              "  php_admin_flag safe_mode on\n",
              "  php_admin_flag safe_mode_gid on\n",
	      "  <Directory $grpdir_prefix/$unix_group_name/htdocs>\n",
	      "    AllowOverride AuthConfig Limit Options Indexes\n",
	      "  </Directory>\n",
	      "  CustomLog logs/vhosts-access_log combined\n",
	      "  ScriptAlias /cgi-bin/ $grpdir_prefix/$unix_group_name/cgi-bin/\n",
	      "</VirtualHost>\n\n");

        # Project Subversion repository
        if ($sys_force_ssl != 1) {
          push @subversion_zone,
            ( "<VirtualHost $ip:80>\n",
                "  ServerName svn.$codex_domain\n",
                "  <Location $svn_prefix/$unix_group_name>\n",
                "    DAV svn\n",
                "    SVNPath $svn_prefix/$unix_group_name\n",
                "    AuthzSVNAccessFile $svn_prefix/$unix_group_name/.SVNAccessFile\n",
                "    Require valid-user\n",
                "    AuthType Basic\n",
                "    AuthName \"Subversion Authorization ($group_name)\"\n",
                "    AuthUserFile $apache_htpasswd\n",
                "  </Location>\n",
                "</VirtualHost>\n\n");
        }
        if ($sys_https_host ne "") {
          # For https, allow access without virtual host because they are not supported
          push @subversion_ssl_zone,
            ( "<Location $svn_prefix/$unix_group_name>\n",
                "    DAV svn\n",
                "    SVNPath $svn_prefix/$unix_group_name\n",
                "    AuthzSVNAccessFile $svn_prefix/$unix_group_name/.SVNAccessFile\n",
                "    Require valid-user\n",
                "    AuthType Basic\n",
                "    AuthName \"Subversion Authorization ($group_name)\n",
                "    AuthUserFile $apache_htpasswd\n",
                "</Location>\n\n");
        }
	
}

write_array_file("$dump_dir/apache_dump", @apache_zone);
write_array_file("$dump_dir/subversion_dump", @subversion_zone);
write_array_file("$dump_dir/subversion_ssl_dump", @subversion_ssl_zone);
