<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 * 
 * 
 *
 * Cross Reference Factory class
 */

require_once('common/reference/CrossReference.class.php');

class CrossReferenceFactory {
    
    var $entity_id;
    var $entity_gid;
    var $entity_type;
    
    /**
     * array of references {Object CrossReference}
     * to the current CrossReferenceFactory 
     * In other words, Items in this array have made references to the current Item
     * @var array
     */
    var $source_refs_datas;
    
    /**
     * array of references {Object CrossReference} made by the current CrossReferenceFaxtory
     * In other words, Items in this array are referenced by the current Item
     * @var array
     */
    var $target_refs_datas;
    
    /** 
     * Constructor 
     * Note that we need a valid reference parameter 
     */
    function CrossReferenceFactory($entity_id,$entity_type,$entity_group_id) {
       $this->entity_id=$entity_id;
       $this->entity_type=$entity_type;
       $this->entity_gid=$entity_group_id;
    }
    
    /**
     * Fill the arrays $this->source_refs_datas and $this->target_refs_datas
     * for the current CrossReferenceFactory  
     */
    function fetchDatas() {
        $sql = "SELECT * 
                FROM cross_references 
                WHERE  (target_gid=" . $this->entity_gid . " AND target_id='" . $this->entity_id . "' AND target_type='" . $this->entity_type . "' ) 
                     OR (source_gid=" . $this->entity_gid." AND source_id='" . $this->entity_id . "' AND source_type='" . $this->entity_type . "' )";
        $res = db_query($sql);
        if ($res && db_numrows($res) > 0) {
        
            $this->source_refs_datas=array();
            $this->target_refs_datas=array();
            
            while ($field_array = db_fetch_array($res)) {                
                
                $target_id=$field_array['target_id'];
                $target_gid=$field_array['target_gid'];
                $target_type=$field_array['target_type'];
                $target_key=$field_array['target_keyword'];
                
                $source_id=$field_array['source_id'];
                $source_gid=$field_array['source_gid'];
                $source_type=$field_array['source_type'];
                $source_key=$field_array['source_keyword'];
                
                $user_id=$field_array['user_id'];
                $created_at=$field_array['created_at'];
                
                if ( ($target_id==$this->entity_id) &&
                     ($target_gid==$this->entity_gid) &&
                     ($target_type==$this->entity_type)
                    ) {
                    $this->source_refs_datas[] = new CrossReference($source_id,$source_gid,$source_type,$source_key,$target_id,$target_gid,$target_type,$target_key,$user_id);
                }
                if ( ($source_id==$this->entity_id) &&
                     ($source_gid==$this->entity_gid) &&
                     ($source_type==$this->entity_type)
                    ) {
                    $this->target_refs_datas[] = new CrossReference($source_id,$source_gid,$source_type,$source_key,$target_id,$target_gid,$target_type,$target_key,$user_id);
                }
            }

        }
    }
        
    function getNbReferences(){
        return (sizeof($this->target_refs_datas) +sizeof($this->source_refs_datas));
    }

    /** Accessors */
    function getRefSource() { return $this->source_refs_datas;}
    function getRefTarget() { return $this->target_refs_datas;}
    
    /**Display function */
    function DisplayCrossRefs() {
        echo $this->getHTMLDisplayCrossRefs();
    }
    
    function getParams($currRef){
        $params = "?target_id=".$currRef->getRefTargetId();
        $params.= "&target_gid=".$currRef->getRefTargetGid();
        $params.= "&target_type=".$currRef->getRefTargetType();
        $params.= "&target_key=".$currRef->getRefTargetKey() ;
        $params.= "&source_id=".$currRef->getRefSourceId();
        $params.= "&source_gid=".$currRef->getRefSourceGid();
        $params.= "&source_type=".$currRef->getRefSourceType();
        $params.= "&source_key=".$currRef->getRefSourceKey() ;
        return $params;
    }
    
    function getMailCrossRefs($format='text') {
        $crossRefArray = $this->getCrossReferences();
        $refs = array();
        foreach ($crossRefArray as $nature => $refArraySourceTarget) {
            foreach (array('both', 'target', 'source') as $key) {
                if ( array_key_exists($key, $refArraySourceTarget) ) {
                    foreach ($refArraySourceTarget[$key] as $currRef) {                        
                        if ($key === 'source') {
                            $ref = $currRef->getRefSourceKey() ." #". $currRef->getRefSourceId();
                            $url = $currRef->getRefSourceUrl();
                        } else {
                            $ref = $currRef->getRefTargetKey() ." #". $currRef->getRefTargetId();
                            $url = $currRef->getRefTargetUrl();
                        }
                        $refs[$key][] = array( 'ref'=>$ref, 'url'=>$url);
                    }
                }
            }
        }        
        return $refs;
    }

    function getHTMLDisplayCrossRefs($with_links = true, $condensed = false, $isBrowser = true) {
        global $Language;

        /**
         * Array of cross references grouped by nature (to easy cross reference display)
         * Array has the form:
         * ['nature1'] => array (
         *                  ['both'] => array (
         *                                  CrossReference1,
         *                                  CrossReference2,
         *                                  ...)
         *                  ['source'] => array (
         *                                  CrossReference3,
         *                                  CrossReference4,
         *                                  ...)
         *                  ['target'] => array (
         *                                  CrossReference3,
         *                                  CrossReference4,
         *                                  ...)
         *  ['nature2'] => array (
         *                  ['both'] => array (
         *                                  CrossReference5,
         *                                  CrossReference6,
         *                                  ...)
         *                  ['source'] => array (
         *                                  CrossReference7,
         *                                  CrossReference8,
         *                                  ...)
         *                  ['target'] => array (
         *                                  CrossReference9,
         *                                  CrossReference10,
         *                                  ...)
         *  ...
         */
         
        $crossRefArray = $this->getCrossReferences();
        
        $reference_manager = ReferenceManager::instance();
        $available_natures = $reference_manager->getAvailableNatures();
        $user = UserManager::instance()->getCurrentUser();
    
        $itemIsReferenced = false;
        if($isBrowser && ($user->isSuperUser() || $user->isMember($this->entity_gid, 'A'))){
                $can_delete = true;
        }else{
                $can_delete = false;
        }
        
        $classes = array(
            'both'   => 'cross_reference',
            'source' => 'referenced_by',
            'target' => 'reference_to',
        );
        $img = array(
            'both'   => array('both_arrows', 'cross_referenced'),
            'source' => array('left_arrow', 'referenced_in'),
            'target' => array('right_arrow', 'reference_to'),
        );
        $message = addslashes($GLOBALS['Language']->getText('cross_ref_fact_include', 'confirm_delete'));
        
         // HTML part (stored in $display)
        $display = '';
        if (!$condensed) {
            $display .= '<p id="cross_references_legend">' . $Language->getText('cross_ref_fact_include','legend') . '</p>';
        }
        // loop through natures
        foreach ($crossRefArray as $nature => $refArraySourceTarget) {
            $display .= '<div class="nature">';
            if (!$condensed) {
                $display .= "<p><b>" . $available_natures[$nature]['label'] . "</b>";
            }
            
            // loop through each type of target
            $display .= '<ul class="cross_reference_list">';
            foreach (array('both', 'target', 'source') as $key) {
                if (array_key_exists($key, $refArraySourceTarget)) {
                    // one li for one type of ref (both, target, source)
                    $display .= '<li class="'. $classes[$key] .'">';
                    $display .= $GLOBALS['HTML']->getImage(
                        'ic/'. $img[$key][0] .'.png',
                        array(
                            'alt'    => $Language->getText('cross_ref_fact_include',$img[$key][1]),
                            'align'  => 'top-left',
                            'hspace' => '5',
                            'title'  => $Language->getText('cross_ref_fact_include',$img[$key][1])
                        )
                    );
                    
                    // the refs
                    $spans = array();
                    foreach ($refArraySourceTarget[$key] as $currRef) {
                        $span = '';
                        if ($key === 'source') {
                            $id  = $currRef->getRefSourceKey() ."_".  $currRef->getRefSourceId();
                            $ref = $currRef->getRefSourceKey() ." #". $currRef->getRefSourceId();
                            $url = $currRef->getRefSourceUrl();
                        } else {
                            $id  = $currRef->getRefTargetKey() ."_".  $currRef->getRefTargetId();
                            $ref = $currRef->getRefTargetKey() ." #". $currRef->getRefTargetId();
                            $url = $currRef->getRefTargetUrl();
                        }
                        $span .= '<span id="' .$id .'" class="link_to_ref">';
                        if ($with_links) {
                            $span .= '<a class="cross-reference" 
                                            title="'. $available_natures[$nature]['label'] .'" 
                                            href="'. $url .'">';
                            $span .= $ref .'</a>';
                        } else {
                            $span.= $ref;
                        }
                        if ($with_links && $can_delete && !$condensed) {
                           $params = $this->getParams($currRef);
                           $span .= '<a class="delete_ref" 
                                           href="/reference/rmreference.php'. $params .'"
                                           onClick="return delete_ref(\''. $id .'\', \''. $message .'\');">';
                           $span .= $GLOBALS['HTML']->getImage(
                               'ic/cross.png', 
                               array( 
                                   'alt'   => $Language->getText('cross_ref_fact_include','delete'),
                                   'title' => $Language->getText('cross_ref_fact_include','delete') 
                               )
                           );
                           $span .= '</a>';
                        }
                        $spans[] = $span;
                    }
                    $display .= implode(', </span>', $spans) .'</span>';
                    $display .= '</li>';
                }
            }
            $display .= "</ul>";
            $display .= "</p>";
            $display .= "</div>";
        }
        
        return $display;
    }

    /**
     * This function retrieves all cross references for given entity id, a group id, and a type
     * @return array cross references data
     */
    protected function getCrossReferences() {
        $crossRefArray = array();

        // Walk the target ref array in order to fill the crossRefArray array
        for ($i=0;$i<sizeof($this->target_refs_datas);$i++) {
            $is_cross = false;
            // Check if the ref is cross referenced (means referenced by a source)
            $j = 0;
            $source_position = 0;
            foreach ($this->source_refs_datas as $source_refs) {
                if ($this->target_refs_datas[$i]->isCrossReferenceWith($source_refs)) {
                   $is_cross = true;
                   $source_position = $j;
                }
                $j++;
            }
            if ($is_cross) {
                if ($this->entity_id == $this->target_refs_datas[$i]->getRefSourceId() &&
                    $this->entity_gid == $this->target_refs_datas[$i]->getRefSourceGid() &&
                    $this->entity_type == $this->target_refs_datas[$i]->getRefSourceType()
                    ) {
                    // Add the cross reference into the "both" (target and source) array
                    $crossRefArray[$this->source_refs_datas[$source_position]->getInsertSourceType()]['both'][] = $this->target_refs_datas[$i];
                } else {
                    $crossRefArray[$this->target_refs_datas[$i]->getInsertSourceType()]['both'][] = $this->target_refs_datas[$i];
                }
            } else {
                // Add the cross reference into the "target" array
                $crossRefArray[$this->target_refs_datas[$i]->getInsertTargetType()]['target'][] = $this->target_refs_datas[$i];
            }
        }

        // Walk the source ref array in order to fill the crossRefArray array
        for ($i=0; $i < sizeof($this->source_refs_datas); $i++) {
            $is_cross = false;
            // Check if the ref is cross referenced (means referenced by a target)
            foreach ($this->target_refs_datas as $target_refs) {
                if ($this->source_refs_datas[$i]->isCrossReferenceWith($target_refs)) {
                   $is_cross = true;
                }
            }
            if ($is_cross) {
                // do nothing, has already been added during target walk
            } else {
                $crossRefArray[$this->source_refs_datas[$i]->getInsertSourceType()]['source'][] = $this->source_refs_datas[$i];
            }
        }

        // Sort array by Nature
        ksort($crossRefArray);
        return $crossRefArray;
    }

}

?>
