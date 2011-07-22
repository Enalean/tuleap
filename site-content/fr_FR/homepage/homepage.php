<?php
/**
 * Copyright (c) Enalean 2011. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

// For backward compatibility: if the introduction speech was 
// customized in etc/site-content homepage.tab, we display him
// instead of following text.
if ($Language->hasText('homepage', 'introduction')) {
    echo stripcslashes($Language->getText('homepage', 'introduction', array($GLOBALS['sys_org_name'], $GLOBALS['sys_name'])));
    return;
}

if (isset($GLOBALS['sys_exchange_policy_url'])) {
    $exchangePolicyUrl = $GLOBALS['sys_exchange_policy_url'];
} else {
    $exchangePolicyUrl = "/plugins/docman/?group_id=1";
}
?>

<div class="slogan">Développement Logiciel Collaboratif chez <?= $GLOBALS['sys_org_name']?></div>

<br><?= $GLOBALS['sys_name']?> est une <b>plate-forme accessible aux équipes de développement logiciel de <?= $GLOBALS['sys_org_name']?></B>. <A href="/docs/site/about_codendi.php">[&nbsp;En savoir plus sur <?= $GLOBALS['sys_name']?>&nbsp;]</A>

<P><?= $GLOBALS['sys_name']?> offre un accès aisé à un environnement de gestion de projet totalement basé sur une interface Web.
En utilisant <?= $GLOBALS['sys_name']?> les équipes de projet peuvent se concentrer sur leurs activités de développement logiciel
tout en privilégiant la relation avec leur communauté d'utilisateurs ou de développeurs.
<A href="/plugins/docman/?group_id=1">[&nbsp;En savoir plus sur les services <?= $GLOBALS['sys_name']?>&nbsp;]</a>

<P><u><B>Participation au site</B></u>
<BR>Pour profiter pleinement de <?= $GLOBALS['sys_name']?>, nous vous recommandons de <A href="/account/register.php">créer un compte utilisateur</a>.
C'est facile, rapide et cela vous permettra de participer au mieux.
Assurez-vous aussi d'avoir lu la  <b><A href="<?php echo $exchangePolicyUrl ?>">Politique d'échange de code de <?= $GLOBALS['sys_org_name']?></a></b> avant d'utiliser les services du site.

<P><u><B>Créer votre propre projet</B></u>
<BR>Après avoir <A href="/account/register.php">créé votre compte utilisateur</A>,
<A HREF="/account/login.php">connectez-vous</A> et <A HREF="/project/register.php">créez votre projet</A>.
Cela ne prend que quelques minutes de mettre en place un environnement de projet totalement opérationnel.

<P><B><U>CLI</U></B>
<BR /><?= $GLOBALS['sys_name']?> vous fournit un Client en LIgne de commande (CLI) basé sur notre <a href="/soap/index.php">API SOAP</a> pour automatiser vos accès à nos services.
Vous pouvez télécharger le <a href="/downloads/Codendi_CLI.zip">Client en Ligne de Commande</a> ainsi que sa <a href="documentation/cli/pdf/fr_FR/Codendi_CLI.pdf">documentation</a>.

<P>Merci... et profitez au mieux du site.

<?php
// Because of to aggressive continuous integration check on closing tags...
?>