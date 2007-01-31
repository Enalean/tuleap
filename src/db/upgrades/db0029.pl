#!/usr/bin/perl

# add permissions to all packages, if they have no permissions set, we add register_user permission
# add permissions to all releases, if they have no permissions set, we add the same permission as their parents

use DBI;

$root_path = "../../";
require $root_path."utils/include.pl";

&db_connect;


sub add_packages_permissions {
	my ($query, $c, $q, $d);

	$query = "SELECT package_id, ugroup_id FROM frs_package LEFT OUTER JOIN permissions ON package_id=object_id AND permission_type='PACKAGE_READ'";

	$c = $dbh->prepare($query);
    	$c->execute();
    	while (my ($package_id, $ugroup_id) = $c->fetchrow()) {
		
			if($ugroup_id=='NULL'){
				$q="INSERT INTO permissions (object_id, permission_type, ugroup_id) VALUES ('".$package_id."', 'PACKAGE_READ', '2')";
				print $q."\n";
				$d = $dbh->prepare($q);
  				$d->execute();
			}

		}
}

sub add_releases_permissions {
	my ($query, $c, $q, $d);

	$query = "SELECT release_id,rp.ugroup_id as r_ugroup_id, pp.ugroup_id as p_ugroup_id FROM frs_release".
			 " LEFT OUTER JOIN  permissions rp ON release_id = rp.object_id and rp.permission_type = 'RELEASE_READ'".
			 " LEFT OUTER JOIN  permissions pp ON package_id = pp.object_id and pp.permission_type = 'PACKAGE_READ'";

	$c = $dbh->prepare($query);
    	$c->execute();
    	while (my ($release_id, $r_ugroup_id, $p_ugroup_id) = $c->fetchrow()) {
		
			if($r_ugroup_id=='NULL'){
				$q="INSERT INTO permissions (object_id, permission_type, ugroup_id) VALUES ('".$release_id."', 'RELEASE_READ',".$p_ugroup_id.")";
				print $q."\n";
				$d = $dbh->prepare($q);
  				$d->execute();
			}

		}
}

add_packages_permissions();
add_releases_permissions();

1;
