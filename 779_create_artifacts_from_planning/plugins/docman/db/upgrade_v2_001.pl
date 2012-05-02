#!/usr/bin/perl -w

# Upgrade templates projects with a default docman if none are existing.

use DBI;

$root_path = "../../../src/";
require $root_path."utils/include.pl";

&db_connect;

sub create_item {
    my ($group_id) = @_;

    $qry = "INSERT INTO plugin_docman_item (parent_id, group_id, title, description, create_date, update_date, delete_date, user_id, status, obsolescence_date, rank, item_type, link_url, wiki_page, file_is_embedded) VALUES (0, $group_id, 'roottitle_lbl_key', '', UNIX_TIMESTAMP(NOW()), UNIX_TIMESTAMP(NOW()), NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL)";

    $create_item = $dbh->prepare($qry);
    $create_item->execute();
}

sub create_permission {
    my ($group_id, $perm, $ugroup_id) = @_;

    $qry_insert_perms = "INSERT INTO permissions(permission_type, ugroup_id, object_id)".
	" SELECT '$perm', $ugroup_id, item_id".
	" FROM plugin_docman_item".
	" WHERE group_id = $group_id";
    
    $insert_perms = $dbh->prepare($qry_insert_perms);
    $insert_perms->execute();
}

sub create_all_perms {
    my ($group_id) = @_;

    create_permission($group_id, "PLUGIN_DOCMAN_READ", 2);
    create_permission($group_id, "PLUGIN_DOCMAN_WRITE", 3);
    create_permission($group_id, "PLUGIN_DOCMAN_MANAGE", 4);

    $qry_admin_perms = "INSERT INTO permissions(permission_type, ugroup_id, object_id)".
	"VALUES ('PLUGIN_DOCMAN_ADMIN', 4, $group_id)";
    $admin_perms = $dbh->prepare($qry_admin_perms);
    $admin_perms->execute();
}


sub create_settings {
    my ($group_id) = @_;

    $qry_settings = "INSERT INTO  plugin_docman_project_settings (group_id, view, use_obsolescence_date, use_status)".
	"VALUES ($group_id, 'Tree', 0, 0)";
    $settings = $dbh->prepare($qry_settings);
    $settings->execute();
}

sub create_docman {
    my ($group_id) = @_;

    create_item($group_id);
    create_all_perms($group_id);
    create_settings($group_id)
}

sub docman_exist {
    my ($group_id) = @_;

    $qry = "SELECT count(*) as nb FROM plugin_docman_item WHERE group_id = $group_id AND delete_date IS NULL";
    $c2 = $dbh->prepare($qry);
    $c2->execute();
    my ($nb) = $c2->fetchrow();
    if($nb gt 0) {
	return 1;
    }
    else {
	return 0;
    }
}

sub create_docman_for_template_projects {
    $qry = "SELECT group_id FROM groups WHERE type='2' and status IN ('A','s')";
    $c = $dbh->prepare($qry);
    $c->execute();
    while (my ($group_id) = $c->fetchrow()) {
	if(docman_exist($group_id)) {
	    print "Docman exist for group $group_id\n";
	}
	else {
	    print "Create docman for group $group_id\n";
	    create_docman($group_id);
	}
    }
}

create_docman_for_template_projects;

1;

