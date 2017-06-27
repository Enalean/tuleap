#!/usr/bin/perl
#
#
#
# ldap.pl - All ldap related functions
#

#############################
#
#############################

use DBI qw(:sql_types);

sub ldap_connect {
    &load_local_config($db_config_file);

    # Connect LDAP
    @servers = split /[ ]*,[ ]*/, $sys_ldap_server;
    $ldap = Net::LDAP->new( \@servers )
      or die "Unable to connect to ldap server: $@\n";
    if ($sys_ldap_bind_dn) {
        $ldap->bind($sys_ldap_bind_dn, password => $sys_ldap_bind_passwd);
    } else {
	$ldap->bind
    }
}

#
# Use a bloc to define the static variables that will cache ldap lookup results.
#
{
    #############################
    #
    #############################

    # Store the map between ldap id and ldap login
    my %ldap_id_array;

    sub ldap_get_ldap_id_from_login {
        my ($ldapLogin) = @_;

        if ( !$ldap_id_array{$ldapLogin} ) {

            #print "LDAP lookup\n";
            my $result = $ldap->search(
                filter => "$sys_ldap_uid=$ldapLogin",
                base   => "$sys_ldap_dn",
                attrs  => [$sys_ldap_uid, $sys_ldap_cn, $sys_ldap_mail, $sys_ldap_eduid]
            );
            if ( $result->count() == 1 ) {
                my @entries = $result->entries;
                $ldap_id_array{$ldapLogin} =
                  $entries[0]->get_value("$sys_ldap_eduid");
            }
            elsif ( $result->count() > 1 ) {
                print STDERR
"There cannot be more than one user with the same ldap login ($ldapLogin).\n";
            }
            else {

                # User not found
                $ldap_id_array{$ldapLogin} = -1;
            }
        }

        return $ldap_id_array{$ldapLogin};
    }

    sub ldap_account_create_auto {
        my ($ldapId) = @_;

        my $codendiId = 0;

        my $result = $ldap->search(
            filter => "$sys_ldap_eduid=$ldapId",
            base   => "$sys_ldap_dn",
            attrs  => [$sys_ldap_uid, $sys_ldap_cn, $sys_ldap_mail, $sys_ldap_eduid]
        );
        if ( $result->count() == 1 ) {
            my @entries = $result->entries;
            $uid       = $entries[0]->get_value($sys_ldap_uid);
            $realname  = $entries[0]->get_value($sys_ldap_cn);
            $email     = $entries[0]->get_value($sys_ldap_mail);

            # Escape values
            $uid =~ s/`/\\`/g;
            $uid =~ s/'/\\'/g;
            $realname =~ s/`/\\`/g;
            $realname =~ s/'/\\'/g;
            $email =~ s/`/\\`/g;
            $email =~ s/'/\\'/g;
            
            my $cmd = "$codendi_utils_prefix/php-launcher.sh ./registerUser.php";
            my $cwd = getcwd();
            chdir("$sys_pluginsroot/ldap/bin");
            $cm = "$cmd --ldapid=$ldapId --realname=$realname --email=$email --uid=$uid";
            @lines = `$cm`;
            foreach my $line (@lines) {
                chomp($line);
                if ( $line =~ m/^ID=(.*):STATUS=(.*)$/ ) {
                    $codendiId = $1;
                }
            }
            chdir($cwd);
        }
        return $codendiId;
    }

    sub ldap_get_svncommitinfo_from_login {
        my ($ldapLogin) = @_;

        my $email    = "";
        my $realname = "";
        my $ldapid   = "";

        my $result = $ldap->search(
            filter => "$sys_ldap_uid=$ldapLogin",
            base   => "$sys_ldap_dn",
            attrs  => [$sys_ldap_uid, $sys_ldap_cn, $sys_ldap_mail, $sys_ldap_eduid]
        );
        if ( $result->count() == 1 ) {
            my @entries = $result->entries;
            $email    = $entries[0]->get_value("$sys_ldap_mail");
            $realname = $entries[0]->get_value("$sys_ldap_cn");
            $ldapid   = $entries[0]->get_value("$sys_ldap_eduid");
        }
        elsif ( $result->count() > 1 ) {
            print STDERR
"There cannot be more than one user with the same ldap login ($ldapLogin).\n";
        }
        else {
            print STDERR "Login doesn't exist ($ldapLogin).\n";
        }

        return ( $realname, $email, $ldapid );
    }

    sub ldap_enabled_for_project {
        my ($group_id) = @_;

        my $query =
            "SELECT NULL"
          . " FROM groups g JOIN plugin_ldap_svn_repository svnrep USING (group_id)"
          . " WHERE svnrep.ldap_auth = 1"
          . " AND g.group_id = ?";
        my $c = $dbh->prepare($query);
        $c->bind_param(1, $group_id, SQL_INTEGER);
        $c->execute();
        return ( $c->rows == 1 );
    }

}

1;
