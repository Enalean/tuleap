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
    
    function getHTMLDisplayCrossRefs() {
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
        
        $reference_manager = ReferenceManager::instance();
    	$available_natures = $reference_manager->getAvailableNatures();
    	$user = UserManager::instance()->getCurrentUser();
    
        $itemIsReferenced = false;
        if($user->isSuperUser() || $user->isMember($this->entity_gid, 'A') ){
                $can_delete = true;
        }else{
                $can_delete = false;
        }
        
         // HTML part (stored in $display)
        $display = '';
       
    	$display .= '<p id="cross_references_legend">' . $Language->getText('cross_ref_fact_include','legend') . '</p>';
    	foreach ($crossRefArray as $nature => $refArraySourceTarget) {
                 $display .= '<div class="nature">';
                 $display .= "<p><b>" . $available_natures[$nature]['label'] . "</b>";
                 $display .= '<ul class="cross_reference_list">';
             
    	    if (array_key_exists('both', $refArraySourceTarget)) {
    	        $display.="<li class='cross_reference'>".$GLOBALS['HTML']->getImage('ic/both_arrows.png', 
                    array( 'alt'=> $Language->getText('cross_ref_fact_include','cross_referenced'),
                            'align' => 'top-left',
                            'hspace' => '5',
                            'title' => $Language->getText('cross_ref_fact_include','cross_referenced') ));
                $i = 0;
                foreach ($refArraySourceTarget['both'] as $currRef) {
                    $id=$currRef->getRefTargetKey()."_".$currRef->getRefTargetId();
                    $message=addslashes($GLOBALS['Language']->getText('cross_ref_fact_include', 'confirm_delete'));
                    $display.="<span id='" .$id ."' class='link_to_ref'>";
                    $display .= "<a  class='cross-reference' title='" . $available_natures[$nature]['label'] . "' href='".$currRef->getRefTargetUrl()."'>";
                    $display.= $currRef->getRefTargetKey()." #".$currRef->getRefTargetId()."</a>";
                    if ($can_delete) {
                           $params = $this->getParams($currRef);
                           $display.="<a  class='delete_ref'  href='/reference/rmreference.php".$params."' ";
                           $display.=" onClick=\"return delete_ref('".$id."','".$message."');\">";
                           $display.=$GLOBALS['HTML']->getImage('ic/cross.png', 
                                array( 'alt'=> $Language->getText('cross_ref_fact_include','delete'),
                                    'title' => $Language->getText('cross_ref_fact_include','delete') ));
                           $display.='</a>';                                                    
                    }
                    $i++;
                    if (count($refArraySourceTarget['both'])!=$i){
                        $display .= ", ";
                    }    
                    $display.='</span>';
                }
                $display .= "</li>";
            }
            
            if (array_key_exists('target', $refArraySourceTarget)) {
                $itemIsReferenced = true;
                $display.="<li class='reference_to'>".$GLOBALS['HTML']->getImage('ic/right_arrow.png', 
                    array( 'alt'=> $Language->getText('cross_ref_fact_include','referenced_in'),
                            'align' => 'top-left',
                            'hspace' => '5',
                            'title' => $Language->getText('cross_ref_fact_include','referenced_in')));
                $i = 0;
                foreach ($refArraySourceTarget['target'] as $currRef) {
                    $id=$currRef->getRefTargetKey()."_".$currRef->getRefTargetId();
                    $message=addslashes($GLOBALS['Language']->getText('cross_ref_fact_include', 'confirm_delete'));
                    $display.="<span id='" .$id ."' class='link_to_ref'>";
                    $display .= "<a  class='cross-reference' title='" . $available_natures[$nature]['label'] . "' href='".$currRef->getRefTargetUrl()."'>";
                    $display.= $currRef->getRefTargetKey()." #".$currRef->getRefTargetId()."</a>";
                     if ($can_delete) {
                            $params = $this->getParams($currRef);
                            $display.="<a class='delete_ref'  href='/reference/rmreference.php".$params."' ";
                            $display.=" onClick=\"return delete_ref('".$id."','".$message."');\">";
                            $display.=$GLOBALS['HTML']->getImage('ic/cross.png', 
                                array( 'alt'=> $Language->getText('cross_ref_fact_include','delete'),
                                    'title' => $Language->getText('cross_ref_fact_include','delete') ));
                            $display.='</a>';
                     }
                     $i++;
                      if (count($refArraySourceTarget['target'])!=$i){
                          $display .= ", ";
                      }    
                    
                    $display.='</span>';
                }
                $display .= "</li>";
            }
            
            if (array_key_exists('source', $refArraySourceTarget)) {
                $display.="<li class='referenced_by'>".$GLOBALS['HTML']->getImage('ic/left_arrow.png', 
                    array( 'alt'=> $Language->getText('cross_ref_fact_include','referenced_in'),
                            'align' => 'top-left',
                            'hspace' => '5',
                            'title' => $Language->getText('cross_ref_fact_include','referenced_in')));
                $i = 0;
        	    foreach ($refArraySourceTarget['source'] as $currRef) {
                    $id=$currRef->getRefSourceKey()."_".$currRef->getRefSourceId();
                    $message=addslashes($GLOBALS['Language']->getText('cross_ref_fact_include', 'confirm_delete'));
                    $display.="<span id='" .$id ."' class='link_to_ref'>";
                    $display .= "<a  class='cross-reference' title='" . $available_natures[$nature]['label'] . "' href='".$currRef->getRefSourceUrl()."'>";
                    $display.= $currRef->getRefSourceKey()." #".$currRef->getRefSourceId()."</a>";
                    if ($can_delete) {
                           $params = $this->getParams($currRef);
                           $display.="<a  class='delete_ref'  href='/reference/rmreference.php".$params."' ";
                           $display.=" onClick=\"return delete_ref('".$id."','".$message."');\">";
                           $display.=$GLOBALS['HTML']->getImage('ic/cross.png', 
                                array( 'alt'=> $Language->getText('cross_ref_fact_include','delete'),
                                    'title' => $Language->getText('cross_ref_fact_include','delete') ));
                           $display.='</a>';
                           
                        }
                        $i++;
                    if (count($refArraySourceTarget['source'])!=$i){
                        $display .= ", ";
                    }    
                    $display.='</span>';
                }
                $display .= "</li>";
            }
            $display .= "</ul>";
            $display .= "</p>";
            $display .= "</div>";
    	}
    	
    	return $display;
    }
    
}

?>