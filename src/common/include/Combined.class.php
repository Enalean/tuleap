<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
class Combined {
    protected function getCombinedScripts() {
        $arr = array(
            '/scripts/polyphills/json2.js',
            '/scripts/polyphills/storage.js',
            '/scripts/prototype/prototype.js',
            '/scripts/protocheck/protocheck.js',
            '/scripts/scriptaculous/scriptaculous.js',
            '/scripts/scriptaculous/builder.js',
            '/scripts/scriptaculous/effects.js',
            '/scripts/scriptaculous/dragdrop.js',
            '/scripts/scriptaculous/controls.js',
            '/scripts/scriptaculous/slider.js',
            '/scripts/scriptaculous/sound.js',
            '/scripts/jquery/jquery-1.9.1.min.js',
            '/scripts/jquery/jquery-ui.min.js',
            '/scripts/jquery/jquery-noconflict.js',
            '/scripts/tuleap/browser-compatibility.js',
            '/scripts/bootstrap/bootstrap-dropdown.js',
            '/scripts/bootstrap/bootstrap-button.js',
            '/scripts/bootstrap/bootstrap-modal.js',
            '/scripts/bootstrap/bootstrap-collapse.js',
            '/scripts/bootstrap/bootstrap-tooltip.js',
            '/scripts/bootstrap/bootstrap-tooltip-fix-prototypejs-conflict.js',
            '/scripts/bootstrap/bootstrap-popover.js',
            '/scripts/bootstrap/bootstrap-select/bootstrap-select.js',
            '/scripts/bootstrap/bootstrap-tour/bootstrap-tour.min.js',
            '/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js',
            '/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker.fr.js',
            '/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker-fix-prototypejs-conflict.js',
            '/scripts/jscrollpane/jquery.mousewheel.js',
            '/scripts/jscrollpane/jquery.jscrollpane.min.js',
            '/scripts/select2/select2.min.js',
            '/scripts/AZHU/storage.js',
            '/scripts/codendi/common.js',
            '/scripts/tuleap/systray.js',
            '/scripts/tuleap/load-systray.js',
            '/scripts/tuleap/massmail_initialize_ckeditor.js',
            '/scripts/tuleap/is-at-top.js',
            '/scripts/tuleap/get-style-class-property.js',
            '/scripts/tuleap/listFilter.js',
            '/scripts/codendi/feedback.js',
            '/scripts/codendi/CreateProject.js',
            '/scripts/codendi/cross_references.js',
            '/scripts/codendi/Tooltip.js',
            '/scripts/codendi/Toggler.js',
            '/scripts/codendi/LayoutManager.js',
            '/scripts/codendi/DropDownPanel.js',
            '/scripts/codendi/colorpicker.js',
            '/scripts/autocomplete.js',
            '/scripts/textboxlist/multiselect.js',
            '/scripts/tablekit/tablekit.js',
            '/scripts/lytebox/lytebox.js',
            '/scripts/lightwindow/lightwindow.js',
            '/scripts/codendi/RichTextEditor.js',
            '/scripts/codendi/Tracker.js',
            '/scripts/codendi/TreeNode.js',
            '/scripts/tuleap/tuleap-modal.js',
            '/scripts/tuleap/tuleap-tours.js',
            '/scripts/placeholder/jquery.placeholder.js',
            '/scripts/tuleap/datetimepicker.js',
        );
        EventManager::instance()->processEvent(Event::COMBINED_SCRIPTS, array('scripts' => &$arr));

        $arr[] = '/scripts/d3/d3.min.js';//last - incompatible with IE7

        return $arr;
    }
    
    public function isCombined($script) {
        return in_array($script, $this->getCombinedScripts());
    }
    
    protected function getDestinationDir() {
        return $GLOBALS['codendi_dir'] .'/src/www/scripts/combined';
    }
    
    protected function getSourceDir($script) {
        $matches = array();
        if (preg_match('`/plugins/([^/]+)/(.*)`', $script, $matches)) {
            return $GLOBALS['sys_pluginsroot'] .'/'. $matches[1] .'/www/'. $matches[2];
        } if (is_file($GLOBALS['codendi_dir'] .'/src/www'. $script)) {
            return $GLOBALS['codendi_dir'] .'/src/www'. $script;
        }

        return $script;
    }
    
    protected function onTheFly() {
        return true;
    }
    
    public function getScripts($scripts) {
        if ($this->onTheFly()) {
            $this->autoGenerate();
        }
        $html = '';
        if (!is_array($scripts)) {
            $scripts = array($scripts);
        }
        $combined = false;
        $combined_scripts = $this->getCombinedScripts();
        $combined_script  = $this->getLatestCombinedScript();
        foreach($scripts as $script) {
            $src = null;
            if (in_array($script, $combined_scripts)) {
                if (!$combined) {
                    $src = $combined_script;
                    $combined = true;
                }
            } else {
                $src = $script;
            }
            if ($src) {
                $html .= '<script type="text/javascript" src="'. $src .'"></script>';
            }
        }
        return $html;
    }
    
    public function generate() {
        foreach($this->getCombinedScripts() as $script) {
            $file = $this->getSourceDir($script);
            if (is_file($file)) {
                file_put_contents(
                    $this->getDestinationDir() . '/codendi-'. $_SERVER['REQUEST_TIME'] .'.js',
                    file_get_contents($file). PHP_EOL,
                    FILE_APPEND
                );
            }
        }
    }
    
    protected function getLatestCombinedScript() {
        $src = $this->getDestinationDir() .'/codendi-';
        $files = glob($src .'*.js');
        if ( !empty($files) ) {
            rsort($files);
            return '/scripts/combined/'. basename($files[0]);
        }
        return '';
    }
    
    public function autoGenerate() {
        $auto_generate = true;
        $combined_script = $this->getLatestCombinedScript();
        if ( empty($combined_script) ) {
            $this->generate();
        } else {
            $date = filemtime($this->getSourceDir($combined_script));
            if (filemtime(__FILE__) < $date) {
                $auto_generate = false;
                foreach($this->getCombinedScripts() as $script) {
                    $file = $this->getSourceDir($script);
                    if ($file && filemtime($file) > $date) {
                        $auto_generate = true;
                        break;
                    }
                }
            }
            if ($auto_generate) {
                unlink($this->getSourceDir($combined_script));
                $this->generate();
            }
        }
    }
}
?>
