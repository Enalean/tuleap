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
require_once dirname(__FILE__).'/../include/Statistics_ServicesUsageDao.class.php';

$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (!$p || !$pluginManager->isPluginAvailable($p)) {
    header('Location: '.get_server_url());
}

//Grant access only to site admin
if (!UserManager::instance()->getCurrentUser()->isSuperUser()) {
    header('Location: '.get_server_url());
}

$request = HTTPRequest::instance();

$error = false;

$vStartDate = new Valid('start');
$vStartDate->addRule(new Rule_Date());
$vStartDate->required();
$startDate = $request->get('start');
if ($request->valid($vStartDate)) {
    $startDate = $request->get('start');
} else {
    $startDate = date('Y-m-d', strtotime('-1 year'));
}

$vEndDate = new Valid('end');
$vEndDate->addRule(new Rule_Date());
$vEndDate->required();
$endDate = $request->get('end');
if ($request->valid($vEndDate)) {
    $endDate = $request->get('end');
} else {
    $endDate = date('Y-m-d', strtotime('+1 month'));
}

if ($startDate >= $endDate) {
    $error = true;
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'period_error'));
}

$groupId  = null;
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $groupId = $request->get('group_id');
}

if (!$error && $request->exist('export')) {
    header('Content-Type: text/csv');
    header('Content-Disposition: filename=scm_stats_'.$startDate.'_'.$endDate.'.csv');
    $statsSvn = new Statistics_Formatter_Svn($startDate, $endDate, $groupId);
    echo $statsSvn->getStats();
    $statsCvs = new Statistics_Formatter_Cvs($startDate, $endDate, $groupId);
    echo $statsCvs->getStats();
    $em = EventManager::instance();
    $params['formatter'] = new Statistics_Formatter($startDate, $endDate, $groupId);
    $em->processEvent('statistics_collector', $params);
    exit;
} else {
    $title = $GLOBALS['Language']->getText('plugin_statistics', 'services_usage');
    $GLOBALS['HTML']->includeCalendarScripts();
    $GLOBALS['HTML']->header(array('title' => $title));
    echo '<h1>'.$title.'</h1>';

    echo '<form name="form_scm_stats" method="get">';
    echo '<table>';
    echo '<tr>';
    echo '<td>';
    echo '<b>'.$GLOBALS['Language']->getText('plugin_statistics', 'scm_start').'</b>';
    echo '</td><td>';
    echo '<b>'.$GLOBALS['Language']->getText('plugin_statistics', 'scm_end').'</b>';
    echo '</td>';
    echo '</tr><tr>';
    echo '<td>';
    list($timestamp,) = util_date_to_unixtime($startDate);
    echo html_field_date('start', $startDate, false, 10, 10, 'form_scm_stats', false);
    echo '</td><td>';
    list($timestamp,) = util_date_to_unixtime($endDate);
    echo html_field_date('end', $endDate, false, 10, 10, 'form_scm_stats', false);
    echo '</td>';
    echo '</tr><tr><td>';
    echo '<input type="submit" name="export" value="'.$GLOBALS['Language']->getText('plugin_statistics', 'scm_export_button').'" >';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';

    $dao = new Statistics_ServicesUsageDao(CodendiDataAccess::instance(), $startDate, $endDate);
    var_dump($dao->getNameOfActiveProjectsBeforeEndDate());

    echo '<hr/>';
    var_dump($dao->getDescriptionOfActiveProjectsBeforeEndDate());

    echo '<hr/>';
    var_dump($dao->getRegisterTimeOfActiveProjectsBeforeEndDate());

    echo '<hr/>';
    var_dump($dao->getInfosFromTroveGroupLink());
    echo '<hr/>';
    var_dump($dao->getAdministrators());
    echo '<hr/>';
    var_dump($dao->getAdministratorsRealNames());
    echo '<hr/>';
    var_dump($dao->getAdministratorsEMails());
    echo '<hr/>';
    var_dump($dao->getCVSActivities());
    echo '<hr/>';
    var_dump($dao->getSVNActivities());
    echo '<hr/>';
    var_dump($dao->getGitActivities());
    echo '<hr/>';
    var_dump($dao->getFilesPublished());

    $GLOBALS['HTML']->footer(array());
}
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
