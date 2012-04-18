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

$main_content_span = 'span12';
$login_input_span  = 'span3';
if ($display_homepage_news) {
    $main_content_span = 'span8';
    $login_input_span  = 'span2';
}

?>


<div class="hero-unit">
    <?= $GLOBALS['HTML']->getImage('organization_logo.png'); ?>
    <h2><?= $GLOBALS['sys_name']?>, le portail collaboratif <br />
    où <?= $GLOBALS['sys_org_name']?> développe des projets logiciels de qualité</h2>
</div>

<div class="row-fluid">
    <div class="<?= $main_content_span ?>">
        <div class="row-fluid">
            <div class="span6">
                <h2><?= $GLOBALS['HTML']->getImage('homepage/tuleap-logo-small.png', array('alt' => "What's Tuleap", 'width' => '48px')) ?> Qu'est-ce que <?= $GLOBALS['sys_name']?>?</h2>
                <p>
                    <b><?= $GLOBALS['sys_name']?> est basé sur Tuleap. Tuleap est là pour vous aider à gérer vos projets logiciels et collaborer avec les membres des équipes.</b>
                </p>
                <p>
                      Tuleap est une Suite libre et Open Source pour la Gestion 
                      du Cycle de vie des Applications (ALM). La Suite fournit 
                      des outils pour gérer les projets, les tâches, les changements, 
                      les incidents ainsi que les documents, le contrôle de version, 
                      l'intégration continue et faciliter la communication. Avec 
                      une seule solution web, chacun peut suivre, développer et 
                      collaborer sur les projets logiciels.
                </p>
                <h3>Avec <?= $GLOBALS['sys_name']?> vous allez pouvoir:</h3>
                <ul>
                    <li>plannifier et gérer vos projets,</li>
                    <li>gérer le développement logiciel: versions de code source, builds d'intégration continue, etc.,</li>
                    <li>suivre les exigences, tâches, bugs, etc.,</li>
                    <li>produire des documents et des livrables,</li>
                    <li>favoriser la collaboration entre les membres du projet.</li>
                </ul>
                <p>
                    <a href="http://www.tuleap.com" target="_blank">Plus d'infos sur Tuleap</a>
                </p>
            </div>
    
            <div class="span6">
                <div class="row-fluid">
                    <h2><?= $GLOBALS['HTML']->getImage('homepage/user.png', array('alt' => "New user", 'width' => '48px')) ?> Participez</h2>
                    <?php if ($current_user->isLoggedIn()) { ?>
                        <p>Bienvenue <?= UserHelper::instance()->getDisplayNameFromUser($current_user) ?>. Vous pouvez profiter du meilleur de <?= $GLOBALS['sys_name']?>. 
                        <a href="/softwaremap/">Rejoignez un projet</a> ou créez en un nouveau.</p>
                    <?php } else { ?>
                        <p>Pour vous permettre d'utiliser toute la puissance de 
                            <?= $GLOBALS['sys_name']?>, vous devez vous enregistrer 
                            ne tant qu'utilisateur. C'est très simple et rapide et 
                            ça vous permettra d'utiliser tout ce que nous avons à 
                            vous offrir.
                        </p>
                        <form action="<?= $login_form_url ?>" method="POST">
                            <input type="text" name="form_loginname" class="<?= $login_input_span ?>" placeholder="Username" />
                            <input type="password" name="form_pw" class="<?= $login_input_span ?>" placeholder="Password" />
                            <input type="submit" class="btn" name="login" value="<?= $GLOBALS['Language']->getText('account_login', 'login_btn') ?>" />
                            Ou <a href="/account/register.php">créez un compte</a>
                        </form>
                    <?php } ?>
                </div>
                <hr />
                <div class="row-fluid">
                    <h2><?= $GLOBALS['HTML']->getImage('homepage/join.png', array('alt' => "Join a project", 'width' => '48px')) ?> Créer un nouveau projet</h2>
                    <?php 
                        $create_your_own_project = 'créer votre propre projet';
                        if ($current_user->isLoggedIn()) {
                            $create_your_own_project = '<a href="/project/register.php">'. $create_your_own_project .'</a>';
                        }
                    ?>
                    <p>C'est très simple de <?= $create_your_own_project ?>.
                        Enregistrez-vous, profitez des modèles de projets et 
                        adaptez votre espace de travail avec l'interface d'adminsitration.</p>
                </div>
            </div>
        </div>
        <?php if ($display_homepage_news) { ?>
        
        <hr />
        
        <div class="row-fluid">
            <div class="span4">
                <h3>Statistiques <?= $GLOBALS['sys_name']?></h3>
                <p>Hosted Projects: <b>66</b>
                    <br />Registered Users: <b>225</b>
                    <br />Files Downloaded: <b>181</b>
                </p>
            </div>
            <div class="span4">
                <h3>Derniers projets</h3>
                <p>(03/26) <a href="/projects/domain/">Hosting - Domains</a>
                    <br />
                            (03/10) <a href="/projects/Tax-Saving/">Taxe-Saving (Belgique)</a>
                    <br />
                            (02/16) <a href="/projects/Labels/">Labels</a>
                    <br />
                            (02/08) <a href="/projects/employees/">Human Resources</a>
                    <br />
                            (01/07) <a href="/projects/POS/">POS</a>
                    <br />
                            (01/07) <a href="/projects/Catalog/">Catalog</a>
                    <br />
                            (01/04) <a href="/projects/mapadherent/">Map adhérent</a>
                    <br />
                            (12/15) <a href="/projects/ISTEAEXP/">Export ISTEA</a>
                    <br />
                            (11/15) <a href="/projects/memberspayplan/">Members Payment plans</a>
                    <br />
                            (10/29) <a href="/projects/Registerisk/">Registerisk</a>
                    <br />
                    <a href="/new/?func=projects">[ More ]</a>
                </p>
    
            </div>
            <div class="span4">
                <h3>Dernières versions</h3>
                <p>(3.1.1)&nbsp;<a href="/projects/hosting/">Module Hosting</a>
                    <br />
                            (0.2.3)&nbsp;<a href="/projects/belgium/">Belgium</a>
                    <br />
                    <a href="/new/?func=releases">[ More ]</a>
                </p>
            </div>
        </div>
        <?php } ?>
    </div>
    <?php if ($display_homepage_news) { ?>
    <div class="span4">
        <h2>Ça se passe sur <?= $GLOBALS['sys_name']?> !</h2>
        <span class="news_summary">
            <a href="/forum/forum.php?forum_id=242"><h3>Milestone 1.0.3.4 is available</h3></a>
        </span>
        <blockquote>Dolibarr 3.2 is needed
                    <br />New: possibility to define a page break
                    <br />Fix: compatibility with latest 3.2 development
                    <small>
                        <span class="news_author">
                            <a href="/users/hregis">Régis Houssin (hregis)</a>
                </span>
                <span class="news_date">
                    <span title="2012-04-12 21:22">19 hours ago</span> in <a href="#">ProjectName</a>
                </span>
            </small>
        </blockquote>
        <span class="news_summary">
            <a href="/forum/forum.php?forum_id=238">New POS module</a>
        </span>
        <blockquote>The DoliPOS module is available on <a target="_blank" href="http://www.dolistore.com">http://www.dolistore.com</a>
            <small>
                <span class="news_author">
                    <a href="/users/simnandez">Juanjo Menent (simnandez)</a>
                </span>
                <span class="news_date">
                    <span title="2012-03-17 10:18">27 days ago</span>
                </span>
            </small>
        </blockquote>
        <span class="news_summary">
            <a href="/forum/forum.php?forum_id=237">Multi-Company 3.1.2 maintenance release</a>
        </span>
        <blockquote>Fix: invalid path if you do not use the custom directory
                    <small>
                        <span class="news_author">
                            <a href="/users/simnandez">Juanjo Menent (simnandez)</a>
                </span>
                <span class="news_date">
                    <span title="2012-03-17 10:18">27 days ago</span>
                </span>
            </small>
        </blockquote>
        <span class="news_summary">
            <a href="/forum/forum.php?forum_id=238">Release of Paypal+ 1.1.0 is available</a>
        </span>
        <blockquote>New: multicurrency module connector with exchange rate <br />
                    Qual: best automatic creation capability <br />
                    Fix: missing customer country
                    <small>
                        <span class="news_author">
                            <a href="/users/simnandez">Juanjo Menent (simnandez)</a>
                </span>
                <span class="news_date">
                    <span title="2012-03-17 10:18">27 days ago</span>
                </span>
            </small>
        </blockquote>
        <span class="news_summary">
            <a href="/forum/forum.php?forum_id=238">New POS module</a>
        </span>
        <blockquote>The DoliPOS module is available on <a target="_blank" href="http://www.dolistore.com">http://www.dolistore.com</a>
            <small>
                <span class="news_author">
                    <a href="/users/simnandez">Juanjo Menent (simnandez)</a>
                </span>
                <span class="news_date">
                    <span title="2012-03-17 10:18">27 days ago</span>
                </span>
            </small>
        </blockquote>
        <ul class="unstyled">
            <li>
                <span class="news_summary">
                    <a href="/forum/forum.php?forum_id=215">Release of Belgium module 0.2.3</a>
                </span>
            </li>
            <li>
                <span class="news_summary">
                    <a href="/forum/forum.php?forum_id=213">Release of Multi-Company 3.2.0-alpha1 is available</a>
                </span>
            </li>
            <li>
                <span class="news_summary">
                    <a href="/forum/forum.php?forum_id=206">Dolibarr Proxmox Appliance 3.1.0-1 is available</a>
                </span>
            </li>
            <li>
                <span class="news_summary">
                    <a href="/forum/forum.php?forum_id=193">Release of Joomla Authentication 1.1.0 is available</a>
                </span>
            </li>
            <li>
                <span class="news_summary">
                    <a href="/forum/forum.php?forum_id=192">Release of Multi-Company 3.1.1 is available</a>
                </span>
            </li>
        </ul>
    </div>
    <?php } ?>
</div>

<?php

//tell the upper script that it should'nt display boxes
$display_homepage_boxes = false;
$display_homepage_news  = false;

?>