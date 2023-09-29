<?php
/**
 * FusionForge nouveau
 *
 * Translated from gwicke's previous TAL template version to remove
 * dependency on PHPTAL.
 *
 * @todo document
 * @file
 * @ingroup Skins
 */

if (! defined('MEDIAWIKI')) {
    die(-1);
}

/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @todo document
 * @ingroup Skins
 */
class SkinTuleap extends SkinTemplate
{
    public const MEDIAWIKI_URL = '/\/plugins\/mediawiki\/wiki\/(.*)\/index.php\//';

    /** Using fusionforge. */
    public $skinname       = 'tuleap';
    public $stylename      = 'tuleap';
    public $template       = 'TuleapTemplate';
    public $useHeadElement = true;

    public function setupTemplate($classname, $repository = false, $cache_dir = false)
    {
            $tc = new $classname();

            $tc->params = [];
        if (
            ($tc->project = $project =
                group_get_object_by_name($GLOBALS['fusionforgeproject']))
        ) {
            $GLOBALS['group_id']       = $project->getID();
                $tc->params['project'] = $project;
                $tc->params['toptab']  = 'plugin_mediawiki';
                //$page_name = substr($_SERVER['REQUEST_URI'], (strpos($_SERVER['REQUEST_URI'], 'index.php/') + strlen('index.php/')), strlen($_SERVER['REQUEST_URI']));
                $page_name           = preg_replace(self::MEDIAWIKI_URL, '', $_SERVER['REQUEST_URI']);
                $tc->params['title'] = 'Mediawiki-' . $page_name;
        }

            return $tc;
    }

    /**
     * @param $out OutputPage
     */
    public function setupSkinUserCss(OutputPage $out)
    {
            global $wgHandheldStyle;
            /* add Tuleap styles */
        foreach ($GLOBALS['HTML']->getAllStyleSheets() as $sheet) {
                $out->addStyle($sheet['css'], $sheet['media']);
        }

            parent::setupSkinUserCss($out);

            $out->addModuleStyles('skins.monobook');

            // Ugh. Can't do this properly because $wgHandheldStyle may be a URL
        if ($wgHandheldStyle) {
                // Currently in testing... try 'chick/main.css'
                $out->addStyle($wgHandheldStyle, 'handheld');
        }

            // TODO: Migrate all of these
            $out->addStyle('tuleap/main.css', 'screen');
            $out->addStyle('tuleap/TuleapSkin.css', 'screen');
            $out->addStyle('tuleap/IE60Fixes.css', 'screen', 'IE 6');
            $out->addStyle('tuleap/IE70Fixes.css', 'screen', 'IE 7');
    }
}

/**
 * @todo document
 * @ingroup Skins
 */
class TuleapTemplate extends BaseTemplate
{
        public $project = false;

    /**
     * Template filter callback for FusionForge skin.
     * Takes an associative array of data set from a SkinTemplate-based
     * class, and a wrapper for MediaWiki's localization database, and
     * outputs a formatted page.
     *
     * @access private
     */
    public function execute()
    {
        global $wgHtml5;
     // Suppress warnings to prevent notices about missing indexes in $this->data
        wfSuppressWarnings();

        $this->html('headelement');

        echo "\n<!-- FUSIONFORGE BodyHeader BEGIN -->\n";
        $GLOBALS['HTML']->header($this->params);
        echo "<div id=\"ff-mw-wrapper\"><div style=\"font-size:x-small;\">\n";
        echo "<!-- FUSIONFORGE BodyHeader END -->\n";

        ?><div id="globalWrapper">
<div id="column-content"><div id="content">
    <a id="top"></a>
        <?php if ($this->data['sitenotice']) {
            ?><div id="siteNotice"><?php $this->html('sitenotice') ?></div><?php
        } ?>

    <h1 id="firstHeading" class="firstHeading"><span<?php if ($wgHtml5) {
        echo ' dir="auto"';
                                                    } ?>><?php $this->html('title') ?></span></h1>
    <div id="bodyContent" class="mw-body">
        <div id="siteSub"><?php $this->msg('tagline') ?></div>
        <div id="contentSub"<?php $this->html('userlangattributes') ?>><?php $this->html('subtitle') ?></div>
        <?php if ($this->data['undelete']) { ?>
        <div id="contentSub2"><?php $this->html('undelete') ?></div>
        <?php } ?><?php if ($this->data['newtalk']) { ?>
        <div class="usermessage"><?php $this->html('newtalk')  ?></div>
        <?php } ?><?php if ($this->data['showjumplinks']) { ?>
        <div id="jump-to-nav" class="mw-jump"><?php $this->msg('jumpto') ?> <a href="#column-one"><?php $this->msg('jumptonavigation') ?></a>, <a href="#searchInput"><?php $this->msg('jumptosearch') ?></a></div>
        <?php } ?>
        <!-- start content -->
        <?php $this->html('bodytext') ?>
        <?php if ($this->data['catlinks']) {
            $this->html('catlinks');
        } ?>
        <!-- end content -->
        <?php if ($this->data['dataAfterContent']) {
            $this->html('dataAfterContent');
        } ?>
        <div class="visualClear"></div>
    </div>
</div></div>
<div id="column-one"<?php $this->html('userlangattributes')  ?>>
        <?php $this->cactions(); ?>
    <div class="portlet" id="p-personal">
        <h5><?php $this->msg('personaltools') ?></h5>
        <div class="pBody">
        <?php
        $ul_shown = false;
        foreach ($this->getPersonalTools() as $key => $item) {
            if (! $ul_shown) {
                ?>
            <ul<?php $this->html('userlangattributes') ?>>
                <?php
                $ul_shown = true;
            }
            echo "\n" . $this->makeListItem($key, $item);
        }
        if ($ul_shown) {
            echo "\n</ul>\n";
        }
        ?>
        </div>
    </div>
        <?php
        $this->renderPortals($this->data['sidebar']);
        ?>
</div><!-- end of the left (by default at least) column -->
<div class="visualClear"></div>
        <?php
        $validFooterIcons = $this->getFooterIcons("icononly");
        $validFooterLinks = $this->getFooterLinks("flat"); // Additional footer links

        if (count($validFooterIcons) + count($validFooterLinks) > 0) { ?>
<div id="footer"<?php $this->html('userlangattributes') ?>>
            <?php
            $footerEnd = '</div>';
        } else {
            $footerEnd = '';
        }
        foreach ($validFooterIcons as $blockName => $footerIcons) { ?>
    <div id="f-<?php echo htmlspecialchars($blockName); ?>ico">
            <?php foreach ($footerIcons as $icon) { ?>
                <?php echo $this->getSkin()->makeFooterIcon($icon); ?>

            <?php }
            ?>
    </div>
        <?php }

        if (count($validFooterLinks) > 0) {
            ?>    <ul id="f-list">
            <?php
            foreach ($validFooterLinks as $aLink) { ?>
        <li id="<?php echo $aLink ?>"><?php $this->html($aLink) ?></li>
                <?php
            }
            ?>
    </ul>
        <?php	}
        echo $footerEnd;
        ?>

</div>
        <?php
        $this->printTrail();
        echo "</div></div>\n";
        $GLOBALS['HTML']->footer($this->params);
        wfRestoreWarnings();
    }

    protected function renderPortals($sidebar)
    {
        if (! isset($sidebar['SEARCH'])) {
            $sidebar['SEARCH'] = true;
        }
        if (! isset($sidebar['TOOLBOX'])) {
            $sidebar['TOOLBOX'] = true;
        }
        if (! isset($sidebar['LANGUAGES'])) {
            $sidebar['LANGUAGES'] = true;
        }

        foreach ($sidebar as $boxName => $content) {
            if ($content === false) {
                continue;
            }

            if ($boxName == 'SEARCH') {
                            //keep empty to remove html search box
            } elseif ($boxName == 'TOOLBOX') {
                $this->toolbox();
            } elseif ($boxName == 'LANGUAGES') {
                $this->languageBox();
            } else {
                $this->customBox($boxName, $content);
            }
        }
    }

    /**
     * Prints the cactions bar.
     * Shared between MonoBook and Modern
     */
    public function cactions()
    {
        ?>
    <div id="p-cactions" class="portlet">
        <h5><?php $this->msg('views') ?></h5>
        <div class="pBody">
            <ul><?php
            foreach ($this->data['content_actions'] as $key => $tab) {
                echo '
				' . $this->makeListItem($key, $tab);
            } ?>

            </ul>
        </div>
    </div>
        <?php
    }

    public function toolbox()
    {
        ?>
    <div class="portlet" id="p-tb">
        <h5><?php $this->msg('toolbox') ?></h5>
        <div class="pBody">
            <ul>
        <?php
        foreach ($this->getToolbox() as $key => $tbitem) { ?>
            <?php echo $this->makeListItem($key, $tbitem); ?>

            <?php
        }
        wfRunHooks('MonoBookTemplateToolboxEnd', [&$this]);
        wfRunHooks('SkinTemplateToolboxEnd', [&$this, true]);
        ?>
            </ul>
        </div>
    </div>
        <?php
    }

    public function languageBox()
    {
        if ($this->data['language_urls']) {
            ?>
    <div id="p-lang" class="portlet">
        <h5<?php $this->html('userlangattributes') ?>><?php $this->msg('otherlanguages') ?></h5>
        <div class="pBody">
            <ul>
            <?php	    foreach ($this->data['language_urls'] as $key => $langlink) { ?>
                <?php echo $this->makeListItem($key, $langlink); ?>

            <?php	    } ?>
            </ul>
        </div>
    </div>
            <?php
        }
    }

    public function customBox($bar, $cont)
    {
        $portletAttribs = ['class' => 'generated-sidebar portlet', 'id' => Sanitizer::escapeId("p-$bar")];
        $tooltip        = Linker::titleAttrib("p-$bar");
        if ($tooltip !== false) {
            $portletAttribs['title'] = $tooltip;
        }
        echo '	' . Html::openElement('div', $portletAttribs);
        ?>

        <h5><?php $msg = wfMessage($bar);
        echo htmlspecialchars($msg->exists() ? $msg->text() : $bar); ?></h5>
        <div class='pBody'>
        <?php   if (is_array($cont)) { ?>
            <ul>
            <?php             foreach ($cont as $key => $val) { ?>
                <?php echo $this->makeListItem($key, $val); ?>

            <?php	        } ?>
            </ul>
        <?php   } else {
            // allow raw HTML block to be defined by extensions
            print $cont;
        }
        ?>
        </div>
    </div>
        <?php
    }
}
