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
				#print $q."\n";
				$d = $dbh->prepare($q);
  				$d->execute();
			}

		}
}

sub add_releases_permissions {
	my ($query_release_perm, $query_package_perm, $res_rel, $res_pack, $q, $d, $find);

	$query_release_perm = "SELECT release_id as rel1, rp.ugroup_id as r_ugroup_id FROM frs_release, permissions rp ".
			 			  "WHERE release_id = rp.object_id and rp.permission_type = 'RELEASE_READ'";
			 
	$query_package_perm = "SELECT release_id as rel2, pp.ugroup_id as p_ugroup_id FROM frs_release, permissions pp ".
			 			  "WHERE package_id = pp.object_id and pp.permission_type = 'PACKAGE_READ'";	 

	$res_rel = $dbh->prepare($query_release_perm);
    $res_rel->execute();
    
    $res_pack = $dbh->prepare($query_package_perm);
    $res_pack->execute();
    
    	while (my ($rel2, $p_ugroup_id) = $res_pack->fetchrow()) {
    		$find = 0;
    		while (my ($rel1, $r_ugroup_id) = $res_rel->fetchrow() && $find == 0){
    			if($rel1 == $rel2) {
    				$find = 1;
    			}
    		}
    		if($find ==0 ){
    			if($p_ugroup_id=='NULL'){   $p_ugroup_id=2; }
    			$q="INSERT INTO permissions (object_id, permission_type, ugroup_id) VALUES ('".$release_id."', 'RELEASE_READ',".$p_ugroup_id.")";
				#print $q."\n";
				$d = $dbh->prepare($q);
  				$d->execute();
    		}
		}
}

add_packages_permissions();
add_releases_permissions();

1;
