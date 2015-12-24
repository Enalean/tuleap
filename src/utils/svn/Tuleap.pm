#!/usr/bin/perl
##
## Copyright (c) Enalean, 2015. All Rights Reserved.
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
use Apache2::ServerRec qw();
use Apache2::RequestRec qw();
use Apache2::RequestUtil qw();
use Apache2::Const qw(:common :override :cmd_how);
use APR::Pool ();
use APR::Table ();
use DBI qw(:sql_types);

my @directives = (
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
        name         => 'TuleapCacheCredsMax',
        req_override => OR_AUTHCFG,
        args_how     => TAKE1,
        errmsg       => 'TuleapCacheCredsMax must be decimal number',
    },
);

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
sub TuleapCacheCredsMax {
    my ($self, $parms, $arg) = @_;
    if ($arg) {
        $self->{TuleapCachePool}       = APR::Pool->new;
        $self->{TuleapCacheCreds}      = APR::Table::make($self->{TuleapCachePool}, $arg);
        $self->{TuleapCacheCredsCount} = 0;
        $self->{TuleapCacheCredsMax}   = $arg;
    }
    return;
}

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

    if (is_user_in_cache($cfg, $username, $user_secret)) {
        return 1;
    }

    my $dbh = DBI->connect($cfg->{TuleapDSN}, $cfg->{TuleapDbUser}, $cfg->{TuleapDbPass}, { AutoCommit => 0 });

    my ($token_id, $hashed_user_secret) = get_user_token($dbh, $project_id, $username, $user_secret);


    if (! $token_id) {
        $hashed_user_secret = get_user_secret_database($dbh, $project_id, $username, $user_secret);
    }

    my $is_allowed = 0;

    if ($hashed_user_secret) {
        add_user_to_cache($cfg, $username, $hashed_user_secret);

        if ($token_id) {
            update_user_token_usage($dbh, $token_id, $r->connection->remote_ip());
        }

        $is_allowed = 1;
    }

    $dbh->disconnect();
    undef $dbh;

    return $is_allowed;
}

sub is_user_in_cache {
    my ($cfg, $username, $user_secret) = @_;

    if (!$cfg->{TuleapCacheCredsMax}) {
        return 0;
    }

    my $user_secret_in_cache = $cfg->{TuleapCacheCreds}->get($username);

    return (defined $user_secret_in_cache and compare_string_constant_time(crypt($user_secret, $user_secret_in_cache), $user_secret_in_cache));
}

sub add_user_to_cache {
    my ($cfg, $username, $hashed_user_secret) = @_;
    if (!$cfg->{TuleapCacheCredsMax}) {
        return 0;
    }

    if ($cfg->{TuleapCacheCredsCount} < $cfg->{TuleapCacheCredsMax}) {
        $cfg->{TuleapCacheCreds}->set($username, $hashed_user_secret);
        $cfg->{TuleapCacheCredsCount}++;
    } else {
        $cfg->{TuleapCacheCreds}->clear();
        $cfg->{TuleapCacheCredsCount} = 0;
    }

    return;
}

sub get_user_token {
    my ($dbh, $project_id, $username, $user_secret) = @_;

    my $query = << 'EOF';
    SELECT id, token
    FROM svn_token
    JOIN user ON user.user_id=svn_token.user_id
    WHERE user.status='A' AND user_name=?
    UNION ALL
    SELECT id, token
    FROM svn_token
    JOIN user ON user.user_id=svn_token.user_id
    JOIN user_group ON user_group.user_id=user.user_id
    WHERE user.status='R' AND user_name=? AND user_group.group_id=?;
EOF

    my $statement = $dbh->prepare($query);
    $statement->bind_param(1, $username, SQL_VARCHAR);
    $statement->bind_param(2, $username, SQL_VARCHAR);
    $statement->bind_param(3, $project_id, SQL_INTEGER);
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

    return ($token_id, $token_secret);
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

sub get_user_secret_database {
    my ($dbh, $project_id, $username, $user_secret) = @_;

    my $query = << 'EOF';
    SELECT unix_pw
    FROM user
    WHERE user.status='A' AND user_name=?
    UNION ALL
    SELECT unix_pw
    FROM user
    JOIN user_group ON user_group.user_id=user.user_id
    WHERE user.status='R' AND user_name=? AND user_group.group_id=?;
EOF
    my $statement = $dbh->prepare($query);
    $statement->bind_param(1, $username, SQL_VARCHAR);
    $statement->bind_param(2, $username, SQL_VARCHAR);
    $statement->bind_param(3, $project_id, SQL_INTEGER);
    $statement->execute();

    my $hashed_user_secret = 0;
    while (my ($row_secret) = $statement->fetchrow_array()) {
        if (compare_string_constant_time(crypt($user_secret, $row_secret), $row_secret)) {
            $hashed_user_secret = $row_secret;
            last;
        }
    }

    $statement->finish();
    undef $statement;

    return $hashed_user_secret;
}

sub compare_string_constant_time {
    my ($string1, $string2) = @_;
    if (length($string1) != length($string2)) {
        return 0;
    }
    my $result = 0;
    for (my $index = 0; $index < length($string1); $index++) {
        $result |= ord(substr($string1, $index, 1)) ^ ord(substr($string2, $index, 1));
    }
    return $result == 0;
}

Apache2::Module::add(__PACKAGE__, \@directives);
1;
