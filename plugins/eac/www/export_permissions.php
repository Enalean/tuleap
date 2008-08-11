
<?php 
  //Document creer le 07/08/08 Par IKRAM BOUOUD
  /*Ce document fait l'export CSV pour les permissions sur les documents et les repertoires*/

require_once('pre.php');
require_once('/project/codex/users/bi16/codev/crx1348-xx-trunk/usr/share/codex/plugins/docman/include/Docman_ItemFactory.class.php');
require_once('/project/codex/users/bi16/codev/crx1348-xx-trunk/usr/share/codex/src/common/include/UserManager.class.php');

//Requete pour identifier le ID du projet 
/////////////////////////////////////////////////////////////
$requete_Id_project="SELECT G.group_id".
    " FROM groups G".
    " WHERE G.group_name='google'";//juste pour le test --> a changer apres

$resultat_Id_project=db_query($requete_Id_project);


//Class ShowPermsVisitor

class ShowPermsVisitor{
    //Attributs
    var  $table_Docman_Totale=array();
    //Constructeurs
    //Methodes

    function visitFolder($item,$table_Docman)
    {
       
        $Id= $item->getId();
        $Title=$item->getTitle();  
        $table_Docman[$Id]=$Title;
        $this->table_Docman_Totale[]=$table_Docman;
        // Walk through the tree
        $items = $item->getAllItems();
        $it =& $items->iterator();
        while($it->valid()) {
            $i =& $it->current();
            $i->accept($this,$table_Docman);
            $it->next();
        }
    }
    function visitWiki($item,$table_Docman)
    {
        $Id= $item->getId();
        $Title=$item->getTitle(); 
        $table_Docman[$Id]=$Title;
        $this->table_Docman_Totale[]=$table_Docman;
    }
    function visitEmpty($item,$table_Docman)
    {
        $Id= $item->getId();
        $Title=$item->getTitle(); 
        $table_Docman[$Id]=$Title;
        $this->table_Docman_Totale[]=$table_Docman;
    }
    function visitEmbeddedFile($item,$table_Docman)
    {
        $Id= $item->getId();
        $Title=$item->getTitle(); 
        $table_Docman[$Id]=$Title;
        $this->table_Docman_Totale[]=$table_Docman;
    }
    function visitLink($item,$table_Docman)
    {
        $Id= $item->getId();
        $Title=$item->getTitle(); 
        $table_Docman[$Id]=$Title;
        $this->table_Docman_Totale[]=$table_Docman;
       
    }
    ///Cette fonction sert a concatener le nom du fichier ou de dossier et d'extraire don Id
    ////////////////////////////////////     
    function Item($Id,&$Liste_item)
    { 
        $item_full_name='';
        $item_id='';
   
        foreach($Id as $id=>$id_doc)     
            {     
                $item_full_name.=$id_doc."/";
                $item_id=$id;
            }
        $Liste_item[$item_id]=$item_full_name;

    }

    ///////////////////////////////////////////
        function MiseEnForme_CSV(&$ugroups,&$Liste_item,$group_id)
        {

            $resultat_ugroups=$this->Liste_ugroups($group_id);
            while($row_ugroups=db_fetch_array($resultat_ugroups))
                {
                    $ugroup_id= $row_ugroups['ugroup_id'];
                    $ugroups[]=$row_ugroups;
                }
            foreach ($this->table_Docman_Totale as $folder_id )
                {
                    $this->Item($folder_id,$Liste_item);  
                }

            foreach($Liste_item as $item_id=>$item)
                {
                    foreach($ugroups as $ugrp){
                        $resultat_permissions=$this->Permissions($group_id,$ugrp['ugroup_id'],$item_id);
                        while ($row_permissions=db_fetch_array($resultat_permissions))
                            {
                                $permission=$this->MiseEnFormePermission($row_permissions['permission_type']);
                                echo "<br>".$item.",".$ugrp['name'].",".$permission;
                            }
      
                    }
                }
        }
        function MiseEnFormePermission($permission)
        {
            if($permission=='PLUGIN_DOCMAN_MANAGE')
                {
                    return '1,1,1';
                }
            else if ($permission=='PLUGIN_DOCMAN_READ')
                {
                    return '1,0,0';
                }
            else if($permission='PLUGIN_DOCMAN_WRITE')
                {
                    return '1,1,0';
                }

        }
        //fonction qui determine la liste des user groups du projet
        //////////////////////////////////////////////////////////////
        function Liste_ugroups($group_id){
            $requete_Liste_ugroups="SELECT Ugrp.ugroup_id, Ugrp.name".
                "  FROM ugroup Ugrp".
                "  WHERE Ugrp.group_id='".$group_id."'";

            $resultat_Liste_ugroups=db_query($requete_Liste_ugroups);
            if($resultat_Liste_ugroups && !db_error($resultat_Liste_ugroups)){
                return $resultat_Liste_ugroups;
            } else {
                echo "DB error: ".db_error()."<br>";
            }


        }

        //fonction pour extraire les permissions sur les fichiers/repertoires 
        //////////////////////////////////////////////////////////////
        function Permissions($group_id, $ugroup_id, $item_id){
            $requete_perms="SELECT  Ugrp.name, P.permission_type, PDI.title".
                "   FROM ugroup Ugrp ".
                "   INNER JOIN permissions P ON(P.ugroup_id=Ugrp.ugroup_id and P.permission_type LIKE 'PLUGIN_DOCMAN%')".
                "   INNER JOIN plugin_docman_item PDI ON(PDI.item_id=P.object_id AND PDI.group_id=Ugrp.group_id)".
                " WHERE P.ugroup_id='".$ugroup_id."'AND Ugrp.group_id='".$group_id."' AND PDI.item_id='".$item_id."'";
            $resultat_perms=db_query($requete_perms);  
            if($resultat_perms && !db_error($resultat_perms))
                {
                    return $resultat_perms;
                } else {
                echo "DB error: ".db_error()."<br>";
            }
        }
        function User_list($ugroup_id,$group_id)
        {
            //requete pour extraire les permissions de l'utilisateur USER sur les items du service doc
            $requete_List="SELECT  U.user_name, Ugrp.name, G.group_name".
                " FROM user U".
                "   INNER JOIN ugroup_user UU ON(U.user_id=UU.user_id)".
                "   INNER JOIN ugroup Ugrp ON( UU.ugroup_id=Ugrp.ugroup_id)".
                "   INNER JOIN groups G ON (Ugrp.group_id=G.group_id)  ".
                " WHERE UU.ugroup_id='".$ugroup_id."'AND Ugrp.group_id='".$group_id."'";
            $resultat_List=db_query($requete_List);
            return  $resultat_List;
	
        }	
        function Liste_user($ugroups,$group_id)
        {
            foreach($ugroups as $ugrp)
                {
                    $resultat_liste_user=$this->User_list($ugrp['ugroup_id'],$group_id);
                    while ($row_liste_user=db_fetch_array($resultat_liste_user))
                        {
                            echo "<br>".$ugrp['name'].",".$row_liste_user['user_name'];
                        }
                }
        }		  

}

$GLOBALS['Language']->loadLanguageMsg('docman', 'docman');

$row_Id_project=db_fetch_array($resultat_Id_project);
$group_id=$row_Id_project['group_id'];

//////////////////////////////////////////
$table_Docman=array();

$um=new UserManager();
$user=$um->getCurrentUser();
$Params['user']=$user;
$Params['ignore_collapse']=true;
$Params['ignore_perms']=true;
$Params['ignore_obsolete']=false;
$itemFactory=new Docman_ItemFactory($group_id);
$node=$itemFactory->getItemTree(0,$Params);
$visitor=new ShowPermsVisitor();
$visitor->visitFolder($node,$table_Docman);
$Liste_item=array();
$ugroups=array();
$visitor->MiseEnForme_CSV($ugroups,$Liste_item,$group_id);
$visitor->Liste_user($ugroups,$group_id);
///////////////////////////////////////////






?> 