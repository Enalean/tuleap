<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'pre.php';

$start_date = '';
$end_date   = '';

$sql = "SELECT group_id, group_name
        FROM groups
        WHERE status='A'
           AND register_time <= $end_date
        GROUP BY group_id;";
$res = db_query($sql);
while($row = db_fetch_array($res)) {
    var_dump($row);
}

echo '<hr/>';

$sql = "SELECT group_id, REPLACE(REPLACE (short_description, CHAR(13),' '),CHAR(10),' ')
        FROM groups
        WHERE status='A'
            AND register_time <= $end_date
        GROUP BY group_id";

$res = db_query($sql);
while($row = db_fetch_array($res)) {
    var_dump($row);
}

echo '<hr/>';

//push(@Allmetrics,new SQLmetrics("Cree le",
//"SELECT group_id, FROM_UNIXTIME(register_time,'%Y-%m-%d')  FROM groups
//WHERE status='A' AND register_time<=$time_end
//GROUP BY group_id"));
//
//push(@Allmetrics,new SQLmetrics("Organisation",
//"SELECT tgl.group_id, tc.shortname  FROM trove_group_link tgl, trove_cat tc
//WHERE tgl.trove_cat_root='281' AND tc.root_parent=tgl.trove_cat_root AND tc.trove_cat_id=tgl.trove_cat_id
//GROUP BY group_id"));
//
//push(@Allmetrics,new SQLmetrics("Administrateur",
//"SELECT g.group_id, u.user_name  FROM user_group g, user u
//WHERE g.user_id=u.user_id AND u.status='A'
//GROUP BY group_id"));
//
//push(@Allmetrics,new SQLmetrics("Nom",
//"SELECT g.group_id, u.realname  FROM user_group g, user u
//WHERE g.user_id=u.user_id AND u.status='A'
//GROUP BY group_id"));
//
//push(@Allmetrics,new SQLmetrics("Email",
//"SELECT g.group_id, u.email  FROM user_group g, user u
//WHERE g.user_id=u.user_id AND u.status='A'
//GROUP BY group_id"));
//
//#push(@Allmetrics,new SQLmetrics("Other_Comments",
//#"SELECT group_id, REPLACE(REPLACE (other_comments, CHAR(13),' '),CHAR(10),' ') FROM groups
//#WHERE status='A' AND register_time<=$time_end
//#GROUP BY group_id"));
//
//#Calcul de l'indicateur 'Activite CVS' par projet
//#date de "l'activite CVS":  group_cvs_full_history.day
//#TODO Activite CVS evaluer la necessite d'inclure d'autre champ dans le calcul exemple: cvs_adds, cvs_checktout, cvs_browse
//push(@Allmetrics,new SQLmetrics("Activite CVS",
//"SELECT group_id, SUM(cvs_commits)
//FROM group_cvs_full_history
//WHERE day<=$cvs_time_end AND day>=$cvs_time_start
//GROUP BY group_id"));
//
//#Calcul de l'indicateur 'Activite SVN' par projet
//#date de "creation du fichier":  group_svn_full_history.day
//#TODO Activite SVN evaluer la necessite d'inclure d'autre champ dans le calcul exemple: svn_adds, svn_checktout, svn_browse, svn_commit
//#TODO Activite SVN contrôler la pertinence de la requete sur le serveur de prod
//#TODO renommer l'indicateur commit SVN
//#NOTE: les champs svn_commit... ne sont pas renseigner dans la base
//push(@Allmetrics,new SQLmetrics("ActiviteSVN",
//"SELECT group_id,COUNT(*)
//FROM  svn_commits
//WHERE date<=$time_end AND date>=$time_start
//GROUP BY group_id"));
//
//push(@Allmetrics,new SQLmetrics("Push Git",
//"SELECT project_id, count(*)
//FROM  plugin_git_log INNER JOIN plugin_git USING(repository_id)
//WHERE push_date<=$time_end AND push_date>=$time_start
//GROUP BY project_id"));
//
//#Calcul de l'indicateur 'Fichiers publies' par projet
//#date de "creation du fichier":  frs_file.postdate
//push(@Allmetrics,new SQLmetrics("Fichiers publie","SELECT p.group_id, COUNT(file_id )
//FROM frs_file f,frs_package p,frs_release r
//WHERE f.release_id= r.release_id AND r.package_id= p.package_id AND f.post_date<=$time_end
//AND f.post_date>=$time_start
//GROUP BY p.group_id"));
//
//#Calcul de l'indicateur 'Fichiers publies (total)' par projet
//#date de "creation du fichier":  frs_file.postdate
//push(@Allmetrics,new SQLmetrics("Fichiers publies (total)","SELECT p.group_id, COUNT( DISTINCT file_id )
//FROM frs_file f,frs_package p,frs_release r
//WHERE f.release_id = r.release_id AND r.package_id = p.package_id AND f.post_date<=$time_end
//GROUP BY p.group_id"));
//
//#Calcul de l'indicateur 'fichiers telecharges (total)' par projet
//#date de "creation du fichier":  frs_file.postdate
//push(@Allmetrics,new SQLmetrics("Fichiers telecharges (total)","SELECT p.group_id, COUNT(filerelease_id )
//FROM filedownload_log l,frs_package p,frs_release r
//WHERE l.filerelease_id = r.release_id AND r.package_id = p.package_id AND l.time<=$time_end
//GROUP BY p.group_id"));
//
//#Calcul de l'indicateur 'Telechargements (periode X jours)' par projet
//#date de "telechargement": frs_dlstats_file_agg.day
//push(@Allmetrics,new SQLmetrics("Telechargements",
//"SELECT p.group_id,SUM(downloads )
//FROM frs_dlstats_file_agg fdl, frs_file f,frs_package p,frs_release r
//WHERE fdl.file_id=f.file_id AND f.release_id = r.release_id AND r.package_id = p.package_id
//AND fdl.day<=$cvs_time_end AND fdl.day>=$cvs_time_start
//GROUP BY p.group_id"));
//
//#Calcul de l'indicateur 'Listes de diffusion actives' par projet
//#valeur des listes detruites: is_public=9
//#TODO date de "creation de la liste":  ?
//push(@Allmetrics,new SQLmetrics("Listes de diffusion actives","SELECT group_id, COUNT( DISTINCT group_list_id )
//FROM mail_group_list
//WHERE is_public!=9
//GROUP BY group_id
//"));
//
//#Calcul de l'indicateur 'Listes de diffusion inactives' par projet
//#valeur des listes detruites: is_public=9
//#TODO date de "creation de la liste":  ?
//push(@Allmetrics,new SQLmetrics("Listes de diffusion inactives","SELECT group_id, COUNT( DISTINCT group_list_id )
//FROM mail_group_list
//WHERE is_public=9
//GROUP BY group_id"));
//
//#Calcul de l'indicateur 'Forums actifs' par projet
//#date de "creation du forum", filtrer les forums n'ayant pas de message avant la date $date dans la table forum
//#NOTE: Le terme 'actif' est trompeur on ne controle pas l'activite du forum mais seulement la presence d'un message avant la date nom de projet 0
//push(@Allmetrics,new SQLmetrics("Forums actifs",
//"SELECT group_id,COUNT( DISTINCT fg.group_forum_id )
//FROM forum_group_list fg, forum f
//WHERE fg.group_forum_id =f.group_forum_id
//AND f.date<=$time_end AND fg.is_public != 9
//GROUP BY  fg.group_id"));
//
//#Calcul de l'indicateur 'Forums inactifs' par projet
//push(@Allmetrics,new SQLmetrics("Forums inactifs",
//"SELECT group_id,COUNT( DISTINCT fg.group_forum_id )
//FROM forum_group_list fg, forum f
//WHERE fg.group_forum_id =f.group_forum_id
//AND f.date<=$time_end AND fg.is_public = 9
//GROUP BY  fg.group_id"));
//
//
//#Calcul de l'indicateur 'Activites Forum' par projet
//#Nombre de message poster sur tout les forums du projet depuis X jours
//#date de "creation du forum", filtrer les forums n'ayant pas de message avant la date $date dans la table forum
//push(@Allmetrics,new SQLmetrics("Activites Forum",
//"SELECT group_id,COUNT(DISTINCT f.msg_id )
//FROM forum_group_list fg, forum f
//WHERE fg.group_forum_id =f.group_forum_id  AND f.date<=$time_end AND f.date>=$time_start
//GROUP BY  fg.group_id"));
//
//#Calcul de l'indicateur 'Documents wiki' par projet
//#date de "creation du document": ? pas de moyens consistant les documents et les pages sont relativement decoreles
//#Il existe un document acceuil cree automatiquement dès que le wiki est initialise
//push(@Allmetrics,new SQLmetrics("Documents wiki",
//"SELECT group_id, COUNT( DISTINCT id) FROM wiki_group_list GROUP BY group_id"));
//
//#Calcul de l'indicateur 'Pages modifies (periode X jours)' par projet
//#date de "creation de la page":plus vieux temps time
//#TODO Tenir compte du problème des pages creees par defaut!
//push(@Allmetrics,new SQLmetrics("Pages modifiees",
//"SELECT group_id, COUNT(pagename) FROM wiki_log
//WHERE time<=$time_end AND time>=$time_start
//GROUP BY group_id"));
//
//#Calcul de l'indicateur 'page wiki' par projet
//#date de "creation de la page":plus vieux temps time
//#TODO Tenir compte du problème des pages creees par defaut!
//push(@Allmetrics,new SQLmetrics("Pages wiki (total)",
//"SELECT group_id, COUNT( DISTINCT pagename) FROM wiki_log
//WHERE time<=$time_end
//GROUP BY group_id"));
//
//# Calcul de l'indicateur 'Artifacts ouverts' par projet
//
//push(@Allmetrics,new SQLmetrics("Artifacts ouverts",
//"SELECT artifact_group_list.group_id,
//COUNT(artifact.artifact_id)
//FROM artifact_group_list, artifact
//WHERE ( open_date >= $time_start AND open_date < $time_end AND artifact_group_list.group_artifact_id = artifact.group_artifact_id )
//GROUP BY artifact_group_list.group_id"));
//
//# Calcul de l'indicateur 'Artifacts fermes' par projet
//
//push(@Allmetrics,new SQLmetrics("Artifacts fermes",
//"SELECT artifact_group_list.group_id,
//COUNT(artifact.artifact_id)
//FROM artifact_group_list, artifact
//WHERE ( close_date >= $time_start
//AND close_date < $time_end
//AND artifact_group_list.group_artifact_id = artifact.group_artifact_id )
//GROUP BY artifact_group_list.group_id"));
//
//# Calcul de l'indicateur 'Utilisateurs ajoutes' par projet
//
//push(@Allmetrics,new SQLmetrics("Utilisateurs ajoutes",
//"SELECT group_id,COUNT(u.user_id)
//FROM user_group ug, user u
//WHERE u.user_id = ug.user_id
//AND add_date>=$time_start
//AND add_date<=$time_end
//GROUP BY  group_id"));
//
//# Extraction du champ 'Code projet' par projet
//
//push(@Allmetrics,new SQLmetrics("Code projet",
//"select g.group_id, value from groups g,group_desc_value gdv, group_desc gd
//WHERE g.group_id = gdv.group_id
//AND gdv.group_desc_id = gd.group_desc_id
//AND gd.desc_name = 'Code projet'
//AND register_time<=$time_end
//GROUP BY g.group_id"));
//
//# Calcul de l'indicateur 'Documents ajoutés' par projet
//
//push(@Allmetrics,new SQLmetrics("Documents ajoutes",
//"select group_id, COUNT(item_id) FROM plugin_docman_item
//WHERE create_date >=$time_start
//AND create_date <=$time_end
//GROUP BY  group_id"));
//
//# Calcul de l'indicateur 'Documents effacés' par projet
//
//push(@Allmetrics,new SQLmetrics("Documents effaces",
//"select group_id, COUNT(item_id) FROM plugin_docman_item
//WHERE delete_date >=$time_start
//AND delete_date <=$time_end
//GROUP BY  group_id"));
//
//# Calcul de l'indicateur 'News publiées' par projet
//
//push(@Allmetrics,new SQLmetrics("News publiees",
//"select group_id, COUNT(id) FROM news_bytes
//WHERE date >=$time_start
//AND date <=$time_end
//GROUP BY  group_id"));
//
//# Calcul de l'indicateur 'Sondages actif' par projet
//
//push(@Allmetrics,new SQLmetrics("Sondages actif",
//"select g.group_id, COUNT(survey_id) FROM surveys s, groups g
//WHERE is_active = 1
//AND g.group_id = s.group_id
//GROUP BY  g.group_id"));
//
//# Calcul de l'indicateur 'Réponses aux sondages publiées' par projet
//
//push(@Allmetrics,new SQLmetrics("Reponses sondages",
//"select group_id, COUNT(*) FROM survey_responses
//WHERE date >=$time_start
//AND date <=$time_end
//GROUP BY  group_id"));
//
//# Verifier si le service 'Integration Continue' est activé par projet
//
//push(@Allmetrics,new SQLmetrics("IntegrationContinueActive",
//"select group_id, is_used from service
//WHERE short_name = 'hudson'
//GROUP BY  group_id"));
//
//# Calcul des jobs existants pour le service 'Integration Continue' par projet
//
//push(@Allmetrics,new SQLmetrics("IntegrationContinueJobs",
//"select group_id, COUNT(*) from plugin_hudson_job
//GROUP BY  group_id"));

?>
