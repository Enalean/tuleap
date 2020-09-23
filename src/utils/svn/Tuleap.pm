#!/usr/bin/perl
##
## Copyright (c) Enalean, 2015-Present. All Rights Reserved.
## Copyright (c) Xerox Corporation, Codendi Team, 2001-2010. All rights reserved
##
## Tuleap is free software; you can redistribute it and/or modify
## it under the terms of the GNU General Public License as published by
## the Free Software Foundation; either version 2 of the License, or
## (at your option) any later version.
##
## Tuleap is distributed in the hope that it will be useful,
## but WITHOUT ANY WARRANTY; without even the implied warranty of
## MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
## GNU General Public License for more details.
##
## You should have received a copy of the GNU General Public License
## along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
##

## This script has been written against the Redmine
## see <http://www.redmine.org/projects/redmine/repository/entry/trunk/extra/svn/Redmine.pm>

package Apache::Authn::Tuleap;

use strict;
use warnings FATAL => 'all', NONFATAL => 'redefine';

use Apache2::Module;
use Apache2::Access;
use Apache2::Connection;
use Apache2::Log qw();
use Apache2::ServerRec qw();
use Apache2::RequestRec qw();
use Apache2::RequestUtil qw();
use Apache2::Const qw(:common :override :cmd_how);
use APR::Pool ();
use APR::Table ();
use DBI qw(:sql_types);
use Net::LDAP;
use Net::LDAP::Util qw(escape_filter_value);
use Digest::SHA qw(hmac_sha256);
use Crypt::Eksblowfish::Bcrypt qw(bcrypt);
use Redis;

my @directives = (
    {
        name         => 'TuleapCacheCredsMax',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
        errmsg       => 'TuleapCacheCredsMax must be decimal number',
    },
    {
        name         => 'TuleapCacheLifetime',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
        errmsg       => 'TuleapCacheLifetime must be decimal number',
    },
    {
        name         => 'TuleapDSN',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
        errmsg       => 'Dsn in format used by Perl DBI. eg: "DBI:Pg:dbname=databasename;host=my.db.server"',
    },
    {
        name         => 'TuleapDbUser',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
    },
    {
        name         => 'TuleapDbPass',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
    },
    {
        name         => 'TuleapGroupId',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
    },
    {
        name         => 'TuleapLdapServers',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
    },
    {
        name         => 'TuleapLdapDN',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
    },
    {
        name         => 'TuleapLdapUid',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
    },
    {
        name         => 'TuleapLdapBindDN',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
    },
    {
        name         => 'TuleapLdapBindPassword',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
    },
    {
        name         => 'TuleapRedisServer',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
    },
    {
        name         => 'TuleapRedisPassword',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
    },
);

sub TuleapCacheCredsMax {
    my ($self, $parms, $arg) = @_;
    if ($arg) {
        my $cache_pool                    = APR::Pool->new;
        $self->{TuleapCacheCreds}         = APR::Table::make($cache_pool->new, $arg);
        $self->{TuleapCacheCredsLifetime} = APR::Table::make($cache_pool->new, $arg);
        $self->{TuleapCacheCredsCount}    = 0;
        $self->{TuleapCacheCredsMax}      = $arg;
    }
    return;
}
sub TuleapCacheLifetime {
    my ($self, $parms, $arg) = @_;
    if ($arg) {
        $self->{TuleapCacheLifetime} = $arg * 60;
    }
    return;
}
sub TuleapDSN {
    my ($self, $parms, $arg) = @_;
    $self->{TuleapDSN} = $arg;
    return;
}
sub TuleapDbUser {
    my ($self, $parms, $arg) = @_;
    $self->{TuleapDbUser} = $arg;
    return;
}
sub TuleapDbPass {
    my ($self, $parms, $arg) = @_;
    $self->{TuleapDbPass} = $arg;
    return;
}
sub TuleapGroupId {
    my ($self, $parms, $arg) = @_;
    $self->{TuleapGroupId} = $arg;
    return;
}
sub TuleapLdapServers {
    my ($self, $parms, $arg) = @_;
    $self->{TuleapLdapServers} = $arg;
    return;
}
sub TuleapLdapDN {
    my ($self, $parms, $arg) = @_;
    $self->{TuleapLdapDN} = $arg;
    return;
}
sub TuleapLdapUid {
    my ($self, $parms, $arg) = @_;
    $self->{TuleapLdapUid} = $arg;
    return;
}
sub TuleapLdapBindDN {
    my ($self, $parms, $arg) = @_;
    $self->{TuleapLdapBindDN} = $arg;
    return;
}
sub TuleapLdapBindPassword {
    my ($self, $parms, $arg) = @_;
    $self->{TuleapLdapBindPassword} = $arg;
    return;
}
sub TuleapRedisServer {
    my ($self, $parms, $arg) = @_;
    $self->{TuleapRedisServer} = $arg;
    return;
}
sub TuleapRedisPassword {
    my ($self, $parms, $arg) = @_;
    $self->{TuleapRedisPassword} = $arg;
    return;
}

my $has_grants_tables_authentication = 0;

sub access_handler {
    my $r = shift;
    if (!$r->some_auth_required) {
        $r->log_reason('No authentication has been configured');
        return FORBIDDEN;
    }
    return OK;
}

sub authen_handler {
    my $r = shift;
    my ($res, $user_secret) = $r->get_basic_auth_pw();

    if ($res != OK) {
        return $res;
    }

    if (is_user_allowed($r, $r->user, $user_secret)) {
        return OK;
    } else {
        $r->note_auth_failure();
        return AUTH_REQUIRED;
    }
}

sub is_user_allowed {
    my ($r, $username, $user_secret) = @_;
    my $cfg        = Apache2::Module::get_config(__PACKAGE__, $r->server, $r->per_dir_config);
    my $project_id = $cfg->{TuleapGroupId};

    if ($cfg->{TuleapRedisServer}) {
        return redis_is_user_allowed($r, $cfg, $project_id, $username, $user_secret);
    }
    return apr_is_user_allowed($r, $cfg, $project_id, $username, $user_secret);
}

sub apr_is_user_allowed {
    my ($r, $cfg, $project_id, $username, $user_secret) = @_;

    if (apr_is_user_in_cache($cfg, $username, $user_secret)) {
        return 1;
    }

    $r->log->notice("[Tuleap.pm][apr] cache miss for $username");

    my $dbh = DBI->connect($cfg->{TuleapDSN}, $cfg->{TuleapDbUser}, $cfg->{TuleapDbPass}, { AutoCommit => 0 });

    my $tuleap_username = get_tuleap_username($cfg, $dbh, $username);

    my $is_user_authenticated = 0;
    if (user_authorization($r->log, $dbh, $project_id, $tuleap_username) &&
        user_authentication($r, $cfg, $dbh, $username, $user_secret, $tuleap_username)) {
        apr_add_user_to_cache($cfg, $username, $user_secret);
        $is_user_authenticated = 1;
    }

    $dbh->disconnect();

    return $is_user_authenticated;
}

sub apr_is_user_in_cache {
    my ($cfg, $username, $user_secret) = @_;

    if (!$cfg->{TuleapCacheCredsMax}) {
        return 0;
    }

    my $user_secret_in_cache = $cfg->{TuleapCacheCreds}->get($username);
    my $cache_entry_age      = $cfg->{TuleapCacheCredsLifetime}->get($username);

    if (not defined $user_secret_in_cache or not defined $cache_entry_age) {
        return 0;
    }

    if ((time() - $cache_entry_age) > $cfg->{TuleapCacheLifetime}) {
        $cfg->{TuleapCacheCreds}->unset($username);
        $cfg->{TuleapCacheCredsLifetime}->unset($username);
        $cfg->{TuleapCacheCredsCount}--;
        return 0;
    }

    my $is_user_in_cache = verify_user_secret($user_secret, $user_secret_in_cache, $cfg);
    if ($is_user_in_cache) {
        $cfg->{TuleapCacheCredsLifetime}->set($username, time())
    }

    return $is_user_in_cache;
}

sub apr_add_user_to_cache {
    my ($cfg, $username, $user_secret) = @_;
    if (!$cfg->{TuleapCacheCredsMax}) {
        return 0;
    }

    if ($cfg->{TuleapCacheCredsCount} >= $cfg->{TuleapCacheCredsMax}) {
        apr_remove_oldest_cache_entry()
    }

    my $hashed_user_secret = hash_user_secret($user_secret, $cfg);
    $cfg->{TuleapCacheCreds}->set($username, $hashed_user_secret);
    $cfg->{TuleapCacheCredsLifetime}->set($username, time());
    $cfg->{TuleapCacheCredsCount}++;

    return;
}

sub apr_remove_oldest_cache_entry {
    my ($cfg)            = @_;
    my $oldest_timestamp = time();
    my $oldest_username;

    foreach my $username (keys %{$cfg->{TuleapCacheCredsLifetime}}) {
        my $timestamp = $cfg->{TuleapCacheCredsLifetime}->get($username);
        if ($oldest_timestamp > $timestamp) {
            $oldest_timestamp = $timestamp;
            $oldest_username  = $username;
        }
    }

    if (defined($oldest_username)) {
        $cfg->{TuleapCacheCreds}->unset($oldest_username);
        $cfg->{TuleapCacheCredsLifetime}->unset($oldest_username);
        $cfg->{TuleapCacheCredsCount}--;
    }

    return;
}

sub redis_is_user_allowed {
    my ($r, $cfg, $project_id, $username, $user_secret) = @_;

    my $is_user_authenticated = 0;
    eval {
        my $redis = Redis->new(server => $cfg->{TuleapRedisServer}, name => 'Tuleap.pm', cnx_timeout => 0.5);
        if ($cfg->{TuleapRedisPassword}) {
            $redis->auth($cfg->{TuleapRedisPassword});
        }

        if (redis_user_authorization($r->log, $cfg, $redis, $username, $project_id) &&
            redis_user_authentication($r, $cfg, $redis, $username, $user_secret)) {
            $is_user_authenticated = 1;
        }

        $redis->quit();
    };
    if ($@) {
        $r->log->warn("Error caught [$@]\n");
        return 0;
    }
    return $is_user_authenticated;
}

sub redis_user_authorization() {
    my ($log, $cfg, $redis, $username, $project_id) = @_;

    my $current_timestamp = time();

    my $cache_key             = "apache_svn_".$username. "_" . $project_id;
    my $cache_set_key         = "apache_svn_project_set_" . $project_id;
    my $cache_timestamp_added = $redis->zscore($cache_set_key, $username);
    if (defined $cache_timestamp_added && ($cache_timestamp_added + $cfg->{TuleapCacheLifetime}) > $current_timestamp) {
        my $cache = $redis->get($cache_key);
        if (defined $cache) {
            return $cache;
        }
    } elsif (defined $cache_timestamp_added) {
        $redis->zremrangebyscore($cache_set_key, "-inf", $current_timestamp - $cfg->{TuleapCacheLifetime});
    }

    $log->notice("[Tuleap.pm][redis] cache miss authorization for $username in $project_id");

    my $dbh = DBI->connect($cfg->{TuleapDSN}, $cfg->{TuleapDbUser}, $cfg->{TuleapDbPass}, { AutoCommit => 0 });
    my $tuleap_username = get_tuleap_username($cfg, $dbh, $username);

    my $user_is_authorized_for_project = user_authorization($log, $dbh, $project_id, $tuleap_username);
    $redis->setex($cache_key, $cfg->{TuleapCacheLifetime}, $user_is_authorized_for_project);
    $redis->zadd($cache_set_key, $current_timestamp, $username);

    $dbh->disconnect();

    return $user_is_authorized_for_project;
}

sub redis_user_authentication() {
    my ($r, $cfg, $redis, $username, $user_secret) = @_;

    my $cache_key                    = "apache_svn_".$username;
    my $user_secret_in_cache         = $redis->get($cache_key);
    if (defined $user_secret_in_cache) {
        if (verify_user_secret($user_secret, $user_secret_in_cache, $cfg)) {
            return 1;
        }
        $redis->del($cache_key);
    }

    $r->log->notice("[Tuleap.pm][redis] cache miss authentication for $username");

    my $dbh = DBI->connect($cfg->{TuleapDSN}, $cfg->{TuleapDbUser}, $cfg->{TuleapDbPass}, { AutoCommit => 0 });
    my $tuleap_username = get_tuleap_username($cfg, $dbh, $username);

    my $user_authentication_success = 0;
    if (user_authentication($r, $cfg, $dbh, $username, $user_secret, $tuleap_username)) {
        my $user_secret_hashed_for_cache = hash_user_secret($user_secret, $cfg);
        $redis->setex($cache_key, $cfg->{TuleapCacheLifetime}, $user_secret_hashed_for_cache);
        $user_authentication_success = 1;
    }

    $dbh->disconnect();

    return $user_authentication_success;
}

sub get_tuleap_username() {
    my ($cfg, $dbh, $username) = @_;

    my $tuleap_username = $username;
    if ($cfg->{TuleapLdapServers}) {
        $tuleap_username = get_tuleap_username_from_ldap_uid($dbh, $username);
    }

    return $tuleap_username;
}

sub user_authorization() {
    my ($log, $dbh, $project_id, $tuleap_username) = @_;

    if (! $tuleap_username || ! can_user_access_project($log, $dbh, $project_id, $tuleap_username)) {
        return 0;
    }

    return 1;
}

sub user_authentication() {
    my ($r, $cfg, $dbh, $username, $user_secret, $tuleap_username) = @_;
    if (! $has_grants_tables_authentication) {
        $has_grants_tables_authentication = has_grants_on_tables_for_authentication($r->log, $dbh);
        if (! $has_grants_tables_authentication) {
            return 0;
        }
    }

    my $is_user_authenticated = 0;
    my $token_id              = get_user_token($dbh, $tuleap_username, $user_secret);

    if ($token_id) {
        $is_user_authenticated = 1;
    } else {
        if (does_user_have_oidc_account($dbh, $tuleap_username)) {
            $r->log->notice("User $username has an OIDC account, only SVN tokens can be used");
            return 0;
        }
        if ($cfg->{TuleapLdapServers}) {
            $is_user_authenticated = is_valid_user_ldap($cfg, $username, $user_secret);
        } else {
            $is_user_authenticated = is_valid_user_database($dbh, $tuleap_username, $user_secret);
        }
    }

    if ($is_user_authenticated && $token_id) {
        my $ip_address = $r->can('useragent_ip') ? $r->useragent_ip : $r->connection->remote_ip;
        update_user_token_usage($dbh, $token_id, $ip_address);
    }

    return $is_user_authenticated;
}


sub get_user_token {
    my ($dbh, $username, $user_secret) = @_;

    my $query = << 'EOF';
    SELECT id, token
    FROM svn_token
    JOIN user ON user.user_id=svn_token.user_id
    WHERE user_name=?;
EOF

    my $statement = $dbh->prepare($query);
    $statement->bind_param(1, $username, SQL_VARCHAR);
    $statement->execute();

    my $token_id     = 0;
    my $token_secret = 0;
    while (my ($row_id, $row_secret) = $statement->fetchrow_array()) {
        if (compare_string_constant_time(crypt($user_secret, $row_secret), $row_secret)) {
            $token_id     = $row_id;
            $token_secret = $row_secret;
            last;
        }
    }

    $statement->finish();
    undef $statement;

    return $token_id;
}

sub update_user_token_usage {
    my ($dbh, $token_id, $ip_address) = @_;

    my $query        = q/UPDATE svn_token SET last_usage=?, last_ip=? WHERE id=?/;
    my $statement    = $dbh->prepare($query);
    $statement->bind_param(1, time, SQL_INTEGER);
    $statement->bind_param(2, $ip_address, SQL_VARCHAR);
    $statement->bind_param(3, $token_id, SQL_INTEGER);
    $statement->execute();
    $statement->finish();
    undef $statement;

    $dbh->commit();
    return;
}

sub can_user_access_project {
    my ($log, $dbh, $project_id, $username) = @_;

    my $query = << 'EOF';
    SELECT NULL
    FROM user, groups
    WHERE user.status='A' AND user_name=? AND groups.group_id=? AND groups.status='A'
    UNION ALL
    SELECT NULL
    FROM user
    JOIN user_group ON user_group.user_id=user.user_id
    JOIN groups ON user_group.group_id = groups.group_id
        AND groups.status = 'A'
    WHERE user.status='R' AND user_name=? AND user_group.group_id=? AND groups.access <> 'private-wo-restr';
EOF
    my $statement = $dbh->prepare($query);
    $statement->bind_param(1, $username, SQL_VARCHAR);
    $statement->bind_param(2, $project_id, SQL_INTEGER);
    $statement->bind_param(3, $username, SQL_VARCHAR);
    $statement->bind_param(4, $project_id, SQL_INTEGER);
    $statement->execute();

    my $can_access = defined($statement->fetchrow_hashref());

    $statement->finish();
    undef $statement;

    if (! $can_access) {
        $log->debug("[Tuleap.pm] Access to project #$project_id has been denied to $username");
    }

    return $can_access;
}

sub does_user_have_oidc_account {
    my ($dbh, $username) = @_;

    if (! does_oidc_table_mapping_exist($dbh)) {
        return 0;
    }

    my $query = << 'EOF';
    SELECT plugin_openidconnectclient_user_mapping.id
    FROM plugin_openidconnectclient_user_mapping
    JOIN user ON (user.user_id = plugin_openidconnectclient_user_mapping.user_id)
    WHERE user.user_name=?;
EOF

    my $statement = $dbh->prepare($query);
    $statement->bind_param(1, $username, SQL_VARCHAR);
    $statement->execute();

    my $has_oidc_account = defined($statement->fetchrow_hashref());

    $statement->finish();
    undef $statement;

    return $has_oidc_account;
}

sub does_oidc_table_mapping_exist {
    my ($dbh) = @_;

    my $query_table_exists = "SHOW TABLES LIKE 'plugin_openidconnectclient_user_mapping'";

    my $statement = $dbh->prepare($query_table_exists);
    $statement->execute();

    my $table_exist = defined($statement->fetchrow_hashref());

    $statement->finish();
    undef $statement;

    return $table_exist;
}

sub is_valid_user_database {
    my ($dbh, $username, $user_secret) = @_;

    my $query = << 'EOF';
    SELECT password
    FROM user
    WHERE user_name=?;
EOF
    my $statement = $dbh->prepare($query);
    $statement->bind_param(1, $username, SQL_VARCHAR);
    $statement->execute();

    my ($row_secret) = $statement->fetchrow_array();

    $statement->finish();
    undef $statement;

    if (!defined $row_secret || substr($row_secret, 0, 4) ne '$2y$') {
        return 0;
    }

    my $submitted_password_hashed = bcrypt_password_hash($user_secret, $row_secret);
    if (defined $submitted_password_hashed) {
        return compare_string_constant_time($submitted_password_hashed, $row_secret);
    }

    return 0;
}

sub bcrypt_password_hash {
    my ($submitted_plaintext_password, $existing_hashed_password) = @_;

    if ($existing_hashed_password =~ m/^\$2y\$(\d{2})\$([A-Za-z0-9+\\\.\/]{22})/) {
        # 2a, 2b and 2y are equivalent in Perl but 2b and 2y identifiers are not accepted
        my $hashed_password_settings = q/$2a$/ . $1 . q/$/ . $2;
        my $submitted_password_hashed = bcrypt($submitted_plaintext_password, $hashed_password_settings);
        $submitted_password_hashed =~ s/^\$2a/\$2y/;
        return $submitted_password_hashed;
    }

    return undef;
}

sub get_tuleap_username_from_ldap_uid {
    my($dbh, $username) = @_;

    my $query = << 'EOF';
    SELECT user_name
    FROM user
    JOIN plugin_ldap_user ON plugin_ldap_user.user_id=user.user_id
    WHERE ldap_uid=?
    AND user.status IN ('A', 'R');
EOF
    my $statement = $dbh->prepare($query);
    $statement->bind_param(1, $username, SQL_VARCHAR);
    $statement->execute();

    my ($tuleap_username) = $statement->fetchrow_array();

    $statement->finish();
    undef $statement;

    return $tuleap_username;
}

sub is_valid_user_ldap {
    my ($cfg, $username, $user_secret) = @_;

    my $ldap = connect_and_bind_ldap($cfg);

    if (! defined($ldap)) {
        return;
    }

    my $user_dn = get_user_dn($cfg, $ldap, $username);
    if (!defined($user_dn)) {
        return;
    }
    my $mesg = $ldap->bind($user_dn, password => $user_secret);
    $ldap->unbind();

    return ! $mesg->code();
}

sub connect_and_bind_ldap {
    my ($cfg) = @_;

    my @servers = split(m/[,]/xms, $cfg->{TuleapLdapServers});
    foreach my $server (@servers) {
        my $ldap = Net::LDAP->new($server, onerror => undef);

        if (defined($ldap) && ldap_bind($cfg, $ldap)) {
            return $ldap;
        }
    }

    return;
}

sub ldap_bind() {
    my ($cfg, $ldap) = @_;
    if ($cfg->{TuleapLdapBindDN}) {
        return $ldap->bind($cfg->{TuleapLdapBindDN}, password => $cfg->{TuleapLdapBindPassword});
    }
    return $ldap->bind();
}

sub get_user_dn() {
    my ($cfg, $ldap, $username) = @_;

    my $mesg = $ldap->search(
        base   => $cfg->{TuleapLdapDN},
        filter => $cfg->{TuleapLdapUid} . q/=/ . escape_filter_value($username),
        scope  => 'sub'
    );

    if (! defined($mesg)) {
        return;
    }

    my $entry = $mesg->shift_entry();
    if ($entry) {
        return $entry->dn();
    }

    return;
}

sub hash_user_secret {
    my ($user_secret, $cfg) = @_;

    my $pepper = $cfg->{TuleapDbPass};
    open(my $urandom_handle, '<', '/dev/urandom') or die('/dev/urandom can not be opened');
    read $urandom_handle, my $salt, 16 or die('Can not read from /dev/urandom');
    close($urandom_handle);

    return password_hashing_for_cache($user_secret, $salt, $pepper);
}

sub verify_user_secret {
    my ($user_secret, $expected_hashed_user_secret, $cfg) = @_;

    my $pepper = $cfg->{TuleapDbPass};
    my $salt = pack("x0 a16", $expected_hashed_user_secret);

    return compare_string_constant_time($expected_hashed_user_secret, password_hashing_for_cache($user_secret, $salt, $pepper))
}

sub password_hashing_for_cache {
    my ($user_secret, $salt, $pepper) = @_;

    return $salt . hmac_sha256(hmac_sha256($user_secret, $salt), $pepper)
}

sub compare_string_constant_time {
    my ($string1, $string2) = @_;
    if (length($string1) != length($string2)) {
        return 0;
    }
    my $result = 0;
    for (0..length($string1)) {
        $result |= ord(substr($string1, $_, 1)) ^ ord(substr($string2, $_, 1));
    }
    return $result == 0;
}

sub has_grants_on_tables_for_authentication {
    my ($log, $dbh) = @_;

    my $statement = $dbh->prepare("SHOW GRANTS");
    $statement->execute();
    my $all_grants = $statement->fetchall_arrayref([0]);

    $statement->finish();
    undef $statement;

    my @tables = ("svn_token", "plugin_ldap_user", "plugin_openidconnectclient_user_mapping");
    for my $table ( @tables ) {
        my $has_grant_table = 0;
        for my $grant ( @{ $all_grants } ) {
            $has_grant_table = $has_grant_table || $grant->[0] =~ /\Q$table\E/
        }

        if (! $has_grant_table) {
            $log->error("GRANT is missing on table $table");
            return 0;
        }
    }

    return 1;
}

Apache2::Module::add(__PACKAGE__, \@directives);
1;
