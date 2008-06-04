<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Define an html field
 */
abstract class HTML_Element {

    protected $params;
    protected $name;
    protected $value;
    protected $label;
    protected $desc;
    protected $id;
    protected static $last_id = 0;
    
    public function __construct($label, $name, $value, $desc='') {
        $this->name   = $name;
        $this->value  = $value;
        $this->label  = $label;
        $this->id     = 'customfield_'. self::$last_id++;
        $this->desc	  = $desc;
        $this->params = array();
    }
    public function getValue() {
        return $this->value;
    }
    protected function renderLabel() {
        $hp = CodeX_HTMLPurifier::instance();
        return '<label for="'. $this->id .'">'.  $hp->purify($this->label, CODEX_PURIFIER_CONVERT_HTML)  .'</label>';
    }
    public function render() {
        $html  = '';
        $html .= $this->renderLabel();
        $html .= '<br />';
        if(trim($this->desc)!=''){
        	$html .=$this->desc.'<br/>';     	
        }
        $html .= $this->renderValue();;
        return $html;
    }
    protected function renderValue() {
        $hp = CodeX_HTMLPurifier::instance();
        return  $hp->purify($this->value, CODEX_PURIFIER_CONVERT_HTML) ;
    }
    public function getId() {
        return $this->id;
    }
}

/*


    function generateSFSelectBox($with_none= false,$on_change = "") {
        $str = "<B>".$this->label."</B>"."<br>"."\n<SELECT NAME=".$this->name;
        if ($on_change != "") {
            $str .= " onChange=\"".$on_change."\">";
        } else {
            $str .= ">";
        }
        $arr = $this->getSFields();
        if($with_none == true) {
            if ($this->value == null) {
                $str .= "\n    <OPTION selected=\"selected\" VALUE=\"\">".$GLOBALS['Language']->getText('plugin_graphontrackers_smf_none','none_value')."</OPTION>";
            } else {
                $str .= "\n    <OPTION VALUE=\"\">".$GLOBALS['Language']->getText('plugin_graphontrackers_smf_none','none_value')."</OPTION>";
            }
        }

        for($i=0;$i<count($arr['field_name']);$i++){
            if ($arr['field_name'][$i] == $this->value) {
                $str .= "\n    <OPTION selected=\"selected\" VALUE=\"".$arr['field_name'][$i]."\">".$arr['label'][$i]."</OPTION>";
            } else {
                $str .= "\n    <OPTION VALUE=\"".$arr['field_name'][$i]."\">".$arr['label'][$i]."</OPTION>";
            }
        }
        $str .= "</SELECT>";
        return $str;
    }

    function generateJsInclude() {
        $js  = "<script type=\"text/javascript\" src=\"/plugins/graphontrackers/dependencies.js\"></script>";
        $js .= "<script type=\"text/javascript\" src=\"/scripts/calendar_js.php\"></script>";
        return $js;
    }



    function generateJsOnClick($form_name,$field_select,$state_select,$with_none = false) {
        $js  = "\n    removeAllOptions($state_select);";
        if ($with_none == true) {
            $js .= "\n    addOption(".$state_select.",'".$GLOBALS['Language']->getText('plugin_graphontrackers_empty_select','none_value')."','',false);";
        }

        $js .= "\n    for (var i=0;i<states.length;i++){";
        $js .= "\n        if(states[i][0] == document.forms['".$form_name."'].".$field_select.".value){";
        $js .= "\n            addOption(".$state_select.",states[i][2],states[i][1],false);";
        $js .= "\n        }";
        $js .= "\n    }";
        return $js;
    }

    function generateTFSelectBox() {
        $str = "<B>".$this->label."</B>"."<br>"."\n<SELECT NAME=".$this->name.">";
        $arr = $this->getTFields();
        for($i=0;$i<count($arr['field_name']);$i++){
            if ($arr['field_name'][$i] == $this->value) {
                $str .= "\n    <OPTION selected=\"selected\" VALUE=\"".$arr['field_name'][$i]."\">".$arr['label'][$i]."</OPTION>";
            } else {
                $str .= "\n    <OPTION VALUE=\"".$arr['field_name'][$i]."\">".$arr['label'][$i]."</OPTION>";
            }
        }

        $str .= "</SELECT>";
        return $str;
    }

    function generateDimSelectBox($with_auto = true) {
        $str = "<B>".$this->label."</B>"."<br>"."\n<SELECT NAME=".$this->name.">";
        if ($with_auto == true) {
            $str .= "\n    <OPTION ";
            if ($this->value == $GLOBALS['Language']->getText('plugin_graphontrackers_graph','automatic')) {
                $str .= "selected=\"selected\" VALUE=\"0\">".$GLOBALS['Language']->getText('plugin_graphontrackers_graph','automatic')."</OPTION>";
            } else {
                $str .= " VALUE=\"0\">".$GLOBALS['Language']->getText('plugin_graphontrackers_graph','automatic')."</OPTION>";
            }
        }
        for($i=50;$i<2000;$i=$i+50) {
            $str .= "\n    <OPTION ";
             if ($this->value == $i) {
                 $str .= "selected=\"selected\" VALUE=\"".$i."\">".$i."</OPTION>";
             } else {
                 $str .= "VALUE=\"".$i."\">".$i."</OPTION>";
             }
        }
        $str .= "</SELECT>";
        return $str;

    }

    function generateSizeSelectBox() {
        $str = "<B>".$this->label."</B>"."<br>"."<SELECT NAME=".$this->name.">";
        for($i=0.1;$i<=0.5;$i=$i+0.1) {
            $str .= "\n    <OPTION ";
             if ($this->value == $i) {
                 $str .= "selected=\"selected\" VALUE=\"".$i."\">".$i."</OPTION>";
             } else {
                 $str .= "VALUE=\"".$i."\">".$i."</OPTION>";
             }
        }
        $str .= "</SELECT>";
        return $str;

    }

    function generateCheckBox(){
        $str = '<input type="hidden" name="'. $this->name .'" value="0" />';
        $str .= '<input type="checkbox" name="'. $this->name .'" value="1"';
        if ($this->value == 1) {
            $str .= ' checked="checked" ';
        }
        $str .= '/> <B>'.$this->label.'</B>';
        return $str;
    }


    function getSFields() {
        require_once('common/tracker/ArtifactFieldFactory.class.php');
        require_once('common/tracker/ArtifactType.class.php');
        $at  = new ArtifactType($GLOBALS['ath']->Group,$GLOBALS['ath']->getID(),false);
        $aff = new ArtifactFieldFactory($at);
        $res = $aff->getAllUsedFields();
        $i = 0;
        foreach ( $res as $key => $value) {
            if (($res[$key]->isSelectBox()) && (!$res[$key]->isUsername()) && (!$res[$key]->isStandardField())) {
                if ($res[$key]->getName() != 'comment_type_id') {
                    $result['field_name'][$i] = $res[$key]->getName();
                    $result['label'][$i] = $res[$key]->getLabel();
                    $i++;
                }
            }
        }
        return $result;
    }

    function getTFields() {
        require_once('common/tracker/ArtifactFieldFactory.class.php');
        require_once('common/tracker/ArtifactType.class.php');
        $at  = new ArtifactType($GLOBALS['ath']->Group,$GLOBALS['ath']->getID(),false);
        $aff = new ArtifactFieldFactory($at);
        $res = $aff->getAllUsedFields();
        $i = 0;
        foreach ( $res as $key => $value) {
            if ($res[$key]->isTextField()) {
                $result['field_name'][$i] = $res[$key]->getName();
                $result['label'][$i] = $res[$key]->getLabel();
                $i++;
            }
         }
        return $result;
    }



}

class ComponentsHTML_SFStateSelectBox extends ComponentsHtml {
    protected $with_none;
    protected $field_value;
    function __construct($name,$value,$label,$field_value,$with_none = false) {
        parent::__construct($name,$value,'SFS',$label);
        $this->with_none   = $with_none;
        $this->field_value = $field_value;
    }
    function getComponentRender() {
        return parent::generateSFStateSelectBox($this->field_value,$this->with_none);
    }                                              
}
class ComponentsHTML_DFSelectBox extends ComponentsHtml {
    protected $with_none;
    function __construct($name,$value,$label,$with_none = false) {
        parent::__construct($name,$value,'DF',$label);
        $this->with_none = $with_none;
    }
    function getComponentRender() {
        return parent::generateDFSelectBox($this->with_none);
    }
}
class ComponentsHTML_SFSelectBox extends ComponentsHtml {
    protected $with_none;
    protected $on_change;
    function __construct($name,$value,$label,$with_none = false, $on_change = "") {
        parent::__construct($name,$value,'SF',$label);
        $this->with_none = $with_none;
        $this->on_change = $on_change;
    }
    function getComponentRender() {
        return parent::generateSFSelectBox($this->with_none, $this->on_change);
    }
}

*/
?>
