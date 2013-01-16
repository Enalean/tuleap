<?php
//
// Copyright (c) Enalean, 2013. All rights reserved
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// This file is licensed under the GNU General Public License version 2. See the file COPYING.
//

$summary_page_link = get_server_url().'/projects/'.$project->getUnixName();

// Directions for project administrators on what to do next
$message .= '<p align="center"><strong>Votre projet '.$project->getPublicName().' a été approuvé.</strong></p>

<p>Informations principales:
<li><strong>Nom complet</strong> :'.$project->getPublicName().'</li>
<li><strong>Nom court du projet</strong> : '.$project->getUnixName().'</li>
<li><strong>Page d\'accueil</strong> :     <a href="'.$summary_page_link.'">'.$summary_page_link.'</a></li>
</p>

<p>Veuillez prendre un peu de temps pour parcourir la <a href="">documentation du
site</a> concernant les outils et services offerts par '.$GLOBALS['sys_name'].'
aux équipes de projet (y compris un guide complet l\'utilisateur).</p>

<p>Nous vous invitons maintenant à visiter la <a href="'.$summary_page_link.'">page de sommaire</a> de votre
projet sur, à écrire une courte description de votre projet et à le classer dans
l\'arbre des projets si vous ne l\'avez pas encore fait.
Tout cela s\'avèrera très utiles aux utilisateurs
du site '.$GLOBALS['sys_name'].'.</p>

<p>En tant qu\'administrateur de projet, vous avez accès à
<a href="'.get_server_url().'/project/admin/?group_id='.$project->getID().'">toutes les fonctions
d\'administration</a> comme créer des outils de suivi de
tâches ou de défauts, créer des listes de distribution, des forums ou encore des
dépôts git ou subversion.</p>

<p>Remarque : si vous possédez déjà un dépôt CVS ou subversion
l\'équipe '.$GLOBALS['sys_name'].' est en mesure de le transférer
tel quel sur le site. N\'hésitez pas à la contacter pour connaître
la marche à suivre.</p>

<p>N\'hésitez pas à nous contacter si vous avez besoin d\'aide.</p>

<p>-- L\'équipe '.$GLOBALS['sys_name'].'</p>';

?>
