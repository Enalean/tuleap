#!/usr/bin/perl

use DBI;

$root_path = "../../";
require $root_path."utils/include.pl";

&db_connect;

print "
Create UpLoad wiki page for wikis created before the CodeX 2.6 upgrade.
The page is created by copying the last 'UpLoad' page created in the server.

IMPORTANT:
- Only apply this patch if you upgraded from CodeX 2.4 or 2.4.1. If you
installed CodeX 2.6 from scratch, please DON'T APPLY THIS PATCH.

- at least one wiki must have been instanciated since CodeX 2.6 upgrade in each supported language.

- you should ignore 'Duplicate entry' errors

continue (y/n)?
";

$line = <STDIN>;
exit if $line=~/n/;

sub correct_upload_wikipage {
  my ($lang_id,$pagename) = @_;
  my ($query, $c, $q, $d);

  # Get group_id of most recently created wiki.
  $query = "SELECT group_id FROM wiki_group_list WHERE wiki_link='$pagename' and language_id=$lang_id ORDER BY id DESC";
  $c = $dbh->prepare($query);
  $c->execute();
  my $recent_group_id=$c->fetchrow();
  if (!$recent_group_id) {return;}

  # Get corresponding UpLoad page ID.
  $query = "SELECT id FROM wiki_page WHERE pagename='UpLoad' AND group_id=$recent_group_id";
  $c = $dbh->prepare($query);
  $c->execute();
  my $recent_page_id=$c->fetchrow();

  # For each UpLoad page:
  $query = "SELECT wiki_page.id,wiki_page.group_id FROM wiki_page,wiki_group_list WHERE pagename='UpLoad' AND wiki_group_list.language_id=$lang_id AND wiki_page.group_id=wiki_group_list.group_id ORDER BY id DESC";
  $c = $dbh->prepare($query);
  $c->execute();
  while (my ($page_id,$group_id) = $c->fetchrow()) {
    next if ($page_id==$recent_page_id);
    print "Updating wiki for group $group_id\n";
    $q = "UPDATE wiki_page as p1, wiki_page as p2 set p2.pagedata=p1.pagedata where p1.id=$recent_page_id and p2.id=$page_id";
    $d = $dbh->prepare($q);
    $d->execute();

    # Check existence of Wiki version
    $q = "SELECT version FROM wiki_version WHERE id=$page_id";
    $d = $dbh->prepare($q);
    $d->execute();
    if ($d->rows == 0) {
      # Create entry
      $q = "INSERT INTO wiki_version (id,version,mtime) VALUES ($page_id,1,1134140317)";
      $d = $dbh->prepare($q);
      $d->execute();
    } 

    # Update wiki_version
    $q = "UPDATE wiki_version as v1, wiki_version as v2 SET v2.content=v1.content where v1.id=$recent_page_id and v2.id=$page_id";
    $d = $dbh->prepare($q);
    $d->execute();
    $q = "UPDATE wiki_version as v1, wiki_version as v2 SET v2.versiondata=v1.versiondata where v1.id=$recent_page_id and v2.id=$page_id";
    $d = $dbh->prepare($q);
    $d->execute();

    # Make sure the page is "non_empty"
    $q = "INSERT INTO wiki_nonempty VALUES ($page_id)";
    $d = $dbh->prepare($q);
    $d->execute();

  }
}


correct_upload_wikipage(1,'HomePage');
correct_upload_wikipage(2,'Accueil');

1;
