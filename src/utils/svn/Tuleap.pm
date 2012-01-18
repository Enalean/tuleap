#!/usr/bin/perl
##
## Copyright (c) Xerox Corporation, Codendi Team, 2001-2010. All rights reserved
##
## Codendi is free software; you can redistribute it and/or modify
## it under the terms of the GNU General Public License as published by
## the Free Software Foundation; either version 2 of the License, or
## (at your option) any later version.
##
## Codendi is distributed in the hope that it will be useful,
## but WITHOUT ANY WARRANTY; without even the implied warranty of
## MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
## GNU General Public License for more details.
##
## You should have received a copy of the GNU General Public License
## along with Codendi. If not, see <http://www.gnu.org/licenses/>.
##

## This script has been written against the Redmine 
## see <http://www.redmine.org/projects/redmine/repository/entry/trunk/extra/svn/Redmine.pm>

package Apache::Authn::Tuleap;

use strict;
use warnings FATAL => 'all', NONFATAL => 'redefine';

use DBI;
use Digest::MD5;

use Apache2::Module;
use Apache2::Access;
use Apache2::ServerRec qw();
use Apache2::RequestRec qw();
use Apache2::RequestUtil qw();
use Apache2::Const qw(:common :override :cmd_how);
use APR::Pool ();
use APR::Table ();

my @directives = (
  {
    name => 'TuleapDSN',
    req_override => OR_AUTHCFG,
    args_how => TAKE1,
    errmsg => 'Dsn in format used by Perl DBI. eg: "DBI:Pg:dbname=databasename;host=my.db.server"',
  },
  {
    name => 'TuleapDbUser',
    req_override => OR_AUTHCFG,
    args_how => TAKE1,
  },
  {
    name => 'TuleapDbPass',
    req_override => OR_AUTHCFG,
    args_how => TAKE1,
  },
{
    name => 'TuleapGroupId',
    req_override => OR_AUTHCFG,
    args_how => TAKE1,
  },

  {
    name => 'TuleapDbWhereClause',
    req_override => OR_AUTHCFG,
    args_how => TAKE1,
  },
  {
    name => 'TuleapCacheCredsMax',
    req_override => OR_AUTHCFG,
    args_how => TAKE1,
    errmsg => 'TuleapCacheCredsMax must be decimal number',
  },
);

sub TuleapDSN {
  my ($self, $parms, $arg) = @_;
  $self->{TuleapDSN} = $arg;
  my $query = "SELECT user_name  FROM user, user_group WHERE (user.status='A' or (user.status='R' AND user_group.user_id=user.user_id and user_group.group_id=?)) AND user_name=? AND user_pw=?";

  $self->{TuleapQuery} = trim($query);
}

sub TuleapDbUser { set_val('TuleapDbUser', @_); }
sub TuleapDbPass { set_val('TuleapDbPass', @_); }
sub TuleapDbWhereClause {
  my ($self, $parms, $arg) = @_;
  $self->{TuleapQuery} = trim($self->{TuleapQuery}.($arg ? $arg : "")." ");
}
sub TuleapGroupId { set_val('TuleapGroupId', @_); }

sub TuleapCacheCredsMax {
  my ($self, $parms, $arg) = @_;
  if ($arg) {
    $self->{TuleapCachePool} = APR::Pool->new;
    $self->{TuleapCacheCreds} = APR::Table::make($self->{TuleapCachePool}, $arg);
    $self->{TuleapCacheCredsCount} = 0;
    $self->{TuleapCacheCredsMax} = $arg;
  }
}

sub trim {
  my $string = shift;
  $string =~ s/\s{2,}/ /g;
  return $string;
}

sub set_val {
  my ($key, $self, $parms, $arg) = @_;
  $self->{$key} = $arg;
}



my %read_only_methods = map { $_ => 1 } qw/GET PROPFIND REPORT OPTIONS/;

sub access_handler {
  my $r = shift;
  unless ($r->some_auth_required) {
      $r->log_reason("No authentication has been configured");
      return FORBIDDEN;
  }
  my $project_id = get_project_identifier($r);
  return OK
}

sub authen_handler {
  my $r = shift;
  my ($res, $tuleap_pass) =  $r->get_basic_auth_pw();
  
  return $res unless $res == OK;

  if (is_allowed($r->user, $tuleap_pass, $r)) {
      return OK;
  } else {
      $r->note_auth_failure();
      return AUTH_REQUIRED;
  }
}

sub is_public_project {
    my $project_id = shift;
    my $r = shift;

    my $dbh = connect_database($r);
    my $sth = $dbh->prepare(
        "SELECT is_public FROM groups WHERE groups.status='A' AND groups.group_id = ?;"
    );

    $sth->execute($project_id);
    my $ret = 0;
    if (my @row = $sth->fetchrow_array) {
    	if ($row[0] eq "1") {
    		$ret = 1;
    	}
    }
    $sth->finish();
    undef $sth;
    $dbh->disconnect();
    undef $dbh;

    $ret;
}

# perhaps we should use repository right (other read right) to check public access.
# it could be faster BUT it doesn't work for the moment.
# sub is_public_project_by_file {
#     my $project_id = shift;
#     my $r = shift;

#     my $tree = Apache2::Directive::conftree();
#     my $node = $tree->lookup('Location', $r->location);
#     my $hash = $node->as_hash;

#     my $svnparentpath = $hash->{SVNParentPath};
#     my $repos_path = $svnparentpath . "/" . $project_id;
#     return 1 if (stat($repos_path))[2] & 00007;
# }

sub is_allowed {
	my $tuleap_user = shift;
	my $tuleap_pass = shift;
	my $r = shift;

	my $project_id  = get_project_identifier($r);

	my $pass_digest = Digest::MD5::md5_hex($tuleap_pass);

	my $cfg = Apache2::Module::get_config(__PACKAGE__, $r->server, $r->per_dir_config);
	my $usrprojpass;
	if ($cfg->{TuleapCacheCredsMax}) {
		$usrprojpass = $cfg->{TuleapCacheCreds}->get($tuleap_user.":".$project_id);
		return 1 if (defined $usrprojpass and ($usrprojpass eq $pass_digest));
	}

	my $query = $cfg->{TuleapQuery};
	my $dbh         = connect_database($r);
	my $sth = $dbh->prepare($query);
	$sth->execute($project_id, $tuleap_user, $pass_digest);

        my $ret = $sth->fetchrow_array; 

	if ($cfg->{TuleapCacheCredsMax} and $ret) {
		if (defined $usrprojpass) {
			$cfg->{TuleapCacheCreds}->set($tuleap_user.":".$project_id, $pass_digest);
		} else {
			if ($cfg->{TuleapCacheCredsCount} < $cfg->{TuleapCacheCredsMax}) {
				$cfg->{TuleapCacheCreds}->set($tuleap_user.":".$project_id, $pass_digest);
				$cfg->{TuleapCacheCredsCount}++;
			} else {
				$cfg->{TuleapCacheCreds}->clear();
				$cfg->{TuleapCacheCredsCount} = 0;
			}
		}
	}
        $sth->finish();
        undef $sth;
        $dbh->disconnect();
        undef $dbh;
	$ret;
}

sub get_project_identifier {
    my $r = shift;
    my $cfg = Apache2::Module::get_config(__PACKAGE__, $r->server, $r->per_dir_config);
    return $cfg->{TuleapGroupId};
}

sub connect_database {
    my $r = shift;
    my $cfg = Apache2::Module::get_config(__PACKAGE__, $r->server, $r->per_dir_config);
    my $dbh = DBI->connect($cfg->{TuleapDSN},$cfg->{TuleapDbUser}, $cfg->{TuleapDbPass}, { AutoCommit => 0});
    return $dbh;
}

Apache2::Module::add(__PACKAGE__, \@directives);
1;
