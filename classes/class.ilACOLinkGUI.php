<?php
include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
include_once("./Services/UIComponent/Explorer2/classes/class.ilExplorerBaseGUI.php");
require_once './Services/Form/classes/class.ilPropertyFormGUI.php';
require_once './Modules/Group/classes/class.ilObjGroup.php';
require_once './Services/Object/classes/class.ilObject2.php';
require_once './Services/Form/classes/class.ilNumberInputGUI.php';
require_once './Services/Form/classes/class.ilTextInputGUI.php';
require_once './Services/Database/classes/class.ilDB.php';
require_once './Services/Form/classes/class.ilRadioGroupInputGUI.php';
require_once './Services/Form/classes/class.ilRadioOption.php';
require_once './Services/Form/classes/class.ilDateTimeInputGUI.php';
/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 06.02.2017
 * Time: 14:16
 * @ilCtrl_IsCalledBy ilACOLinkGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilACOLinkGUI: ilExerciseHandlerGUI, ilRepositoryGUI, ilObjTestGUI
 */

class ilACOLinkGUI{
    /**
     * @var ilCtrl
     */
    protected $ctrl;
    /**
     * @var ilTemplate
     */
    protected $tpl;
    /**
     * @var ilACOPlugin
     */
    protected $pl;
    /**
     * @var ilTabsGUI
     */
    protected $tabs;
    /**
     * @var ilLocatorGUI
     */
    protected $ilLocator;
    /**
     * @var ilLanguage
     */
    protected $lng;

    var $object;

    public function __construct()
    {
        global  $ilCtrl, $tpl, $ilTabs, $ilLocator, $lng;
        $this->unique_id = md5(uniqid());
        //var_dump($this->unique_id);
        $this->lng = $lng;
        $this->tabs = $ilTabs;
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->ilLocator = $ilLocator;
        $this->pl = ilACOPlugin::getInstance();
    }

    protected function prepareOutput()
    {
        global $ilLocator, $tpl;

        $this->ctrl->setParameterByClass('ilexercisehandlergui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilobjtestgui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilacolinkgui', 'ref_id', $_GET['ref_id']);

        $this->tabs->addTab('link', $this->pl->txt('tab_link'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilACOLinkGUI')));

        $this->ctrl->getRedirectSource();

        if (ilObject::_lookupType($_GET['ref_id'], true) == 'tst'){

            $this->tabs->setBackTarget($this->pl->txt('back'), $this->ctrl->getLinkTargetByClass(array(
                'ilrepositorygui',
                'ilObjTestGUI',
            )));

            $this->object = ilObjectFactory::getInstanceByRefId($_GET['ref_id']);
            $ilLocator->addRepositoryItems($_GET['ref_id']);
            $ilLocator->addItem($this->object->getTitle(),
                $this->ctrl->getLinkTargetByClass(array('ilrepositorygui','ilObjTestGUI')), "", $_GET["ref_id"]);

        } else {

            $this->tabs->setBackTarget($this->pl->txt('back'), $this->ctrl->getLinkTargetByClass(array(
                'ilrepositorygui',
                'ilExerciseHandlerGUI',
            )));

            $this->object = ilObjectFactory::getInstanceByRefId($_GET['ref_id']);
            $ilLocator->addRepositoryItems($_GET['ref_id']);
            $ilLocator->addItem($this->object->getTitle(),
                $this->ctrl->getLinkTargetByClass(array('ilrepositorygui','ilExerciseHandlerGUI')), "", $_GET["ref_id"]);

        }
        $this->setTitleAndIcon();


        $tpl->setLocator();
    }
    protected function setTitleAndIcon()
    {
        if (ilObject::_lookupType($_GET['ref_id'], true) == 'tst'){
            $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_tst.svg'));
        }else {
            $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_exc.svg'));
        }
        $this->tpl->setTitle($this->pl->txt('obj_link'));
        $this->tpl->setDescription($this->pl->txt('obj_link_desc'));
    }
    public function executeCommand()
    {
        $this->checkAccess();
        $cmd = $this->ctrl->getCmd('view');
        $this->ctrl->saveParameter($this, 'ref_id');
        $this->prepareOutput();
        //var_dump($cmd);
        switch ($cmd) {
            default:
                $this->$cmd();
                break;
        }

        $this->tpl->getStandardTemplate();
        $this->tpl->show();
    }

    /**
     * default command
     */
    protected function view() {

        $form = $this->initForm();
        $this->tpl->setContent($form->getHTML());

    }

    protected function initForm()
    {

        $form = new ilPropertyFormGUI();
        $form->setTitle($this->pl->txt('link_exercise'));
        $form->setFormAction($this->ctrl->getFormAction($this));
        $data = $this->getGroups($_GET['ref_id']);

        //var_dump($data);

        //radio button to select if to link to all groups or only to single ones
        $link_proc = new ilRadioGroupInputGUI($this->pl->txt('link_type'),'link_type');
        $opt1 = new ilRadioOption($this->pl->txt('link_all'), 'link_all');
        $opt2 = new ilRadioOption($this->pl->txt('link_selected'), 'link_selected');

        foreach ($data as $row){

            $checkbox_link = new ilCheckboxInputGUI($row['title'], $row['ref_id']);
            //$checkbox_link->setValue($this->isReferenced($row['obj_id'],$_GET['ref_id']));
            $opt2->addSubItem($checkbox_link);

        }

        $link_proc->addOption($opt1);
        $link_proc->addOption($opt2);

        $form->addItem($link_proc);
        $form->addCommandButton('link',$this->pl->txt('save_link'));
        return $form;
    }

    protected function isReferenced($group_id,$exercise_id){

    }




    protected function getAdminFolderIds(){
        $ids = array();
        $group_ids = array();
        $form = $this->initForm();
        $form->setValuesByPost();

        $formitems = $form->getItems();

        $checkFormItems = $formitems[0]->getValue();

        $formitems = $formitems[0]->getOptions();
        $formitems = $formitems[1]->getSubItems();

        $folder_name = $this->getFolderName();

        foreach($formitems as $checkbox){
            var_dump($checkFormItems);
            if (($checkFormItems)=="link_all"){
                $checkbox->setChecked(true);
            }
            if(!is_null($checkbox->getChecked())){

                $group_id = $checkbox->getPostVar();

                $grid = array();
                $grid[0]=$group_id;
                array_push($group_ids,$grid);
                $folder_id = $this->getGroupFolderID($group_id,$folder_name);
                if($folder_id == -2){
                    ilUtil::sendInfo('inf_group_no_named_folder'."'.$folder_name.'".'msg_linked_to_group_directory');
                    $folder_id = array();
                    $folder_id[0]=$group_id;
                    array_push($ids,$folder_id);
                }else {
                    array_push($ids, $folder_id);
                }
            }
        }
        if($folder_name == -1){
            return $group_ids;
        }
        return $ids;
    }

    protected function getFolderName(){
        global $ilDB;

        $exc_id = $_GET['ref_id'];
        $folder_id = $this->getParentIds($exc_id);

        //get Name of Parent Folder from Exercise
        $data = array();
        $query = "select od.title from ilias.object_data as od 
                  join ilias.object_reference as oref on od.obj_id = oref.obj_id
                  where oref.ref_id = '".$folder_id[0]."' and od.type = 'fold' ";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        //var_dump($data);
        if(empty($data)){
            ilUtil::sendInfo($this->pl->txt('inf_parent_no_folder'));
            return -1;
        }
        $folder = $data[0];
        return $folder['title'];
    }
    protected function getParentIds($id){

        global $ilDB;

        $ids = array();
        $data = array();
        $query = "select tree.parent from ilias.tree as tree where child = '".$id."'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        foreach ($data as $folder){
            array_push($ids,$folder['parent']);
        }
        return $ids;

    }


    protected function getGroupFolderID($group_id,$folder_name){
        global $ilDB;
        if($folder_name == -1){
            $return = array();
            $return[0] = $group_id;
            return $return;
        }
        $data = array();
        $query = "select folds.ref_id from (
        select tree.parent, oref.ref_id, od.title
        from ilias.object_data as od 
        join ilias.object_reference as oref on od.obj_id = oref.obj_id
        join ilias.tree as tree on tree.child = oref.ref_id
        where od.type = 'fold') as folds
        where folds.parent = '".$group_id."' and folds.title = '".$folder_name."'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        if(empty($data)){
            return -2;
        }
        $ids = array();
        foreach ($data as $folder){
            array_push($ids,$folder['ref_id']);
        }
        return $ids;

    }



    protected function link()
    {
        global $rbacreview, $log, $tree, $ilObjDataCache, $ilUser;


            $linked_to_folders = array();

            include_once "Services/AccessControl/classes/class.ilRbacLog.php";
            include_once "Services/Tracking/classes/class.ilChangeEvent.php";
            $rbac_log_active = ilRbacLog::isActive();

            $group_admin_folder_ids = $this->getAdminFolderIds();

            //var_dump($group_admin_folder_ids);

            foreach($group_admin_folder_ids as $folder_ref_id)
            {
                $linked_to_folders[] = $ilObjDataCache->lookupTitle($ilObjDataCache->lookupObjId($folder_ref_id[0]));

                             
                //get Ref_id of Excercise or Test you want to link
                $ref_id = $_GET['ref_id'];
         
                //bin mir ehrlich gesagt nicht sicher ob folder_ref_id die ref_id der Gruppen ist in die gelinked wird
                //jedenfalls funktioniert das ganze noch nicht so richtig
                
                $objId = $this->getObjIdFromLinkedObject($ref_id);
                $isAlreadyLinked = $this->isAlreadyLinked($folder_ref_id, $objId);
                               
         //       if( $isAlreadyLinked == true){
           //         break;
            //    }

                    // get node data
                    $top_node = $tree->getNodeData($ref_id);

                    // get subnodes of top nodes
                    $subnodes[$ref_id] = $tree->getSubtree($top_node);


                // now move all subtrees to new location
                foreach($subnodes as $key => $subnode)
                {
                    // first paste top_node....
                    $obj_data = ilObjectFactory::getInstanceByRefId($key);
                    $new_ref_id = $obj_data->createReference();
                    $obj_data->putInTree($folder_ref_id[0]);
                    $obj_data->setPermissions($folder_ref_id[0]);

                    // rbac log
                    if($rbac_log_active)
                    {
                        $rbac_log_roles = $rbacreview->getParentRoleIds($new_ref_id, false);
                        $rbac_log = ilRbacLog::gatherFaPa($new_ref_id, array_keys($rbac_log_roles), true);
                        ilRbacLog::add(ilRbacLog::LINK_OBJECT, $new_ref_id, $rbac_log, $key);
                    }

                    // BEGIN ChangeEvent: Record link event.
                    $node_data = $tree->getNodeData($new_ref_id);
                    ilChangeEvent::_recordWriteEvent($node_data['obj_id'], $ilUser->getId(), 'add',
                        $ilObjDataCache->lookupObjId($folder_ref_id[0]));
                    ilChangeEvent::_catchupWriteEvents($node_data['obj_id'], $ilUser->getId());
                    // END PATCH ChangeEvent: Record link event.
                }

                $log->write(__METHOD__.', link finished');


            ilUtil::sendSuccess(sprintf($this->lng->txt('mgs_objects_linked_to_the_following_folders'), implode(', ', $linked_to_folders)), true);
        } // END LINK
    }


    protected function getGroups($ref_id){
        global $ilDB;

        do {
            $parent_id = $this->getParentIds($ref_id);
            $ref_id = $parent_id[0];
        }while (!$this->isCourse($ref_id));



        $data = array();
        $query = "select od.title, oref.ref_id
                    from ilias.object_data as od
                    join ilias.object_reference as oref on oref.obj_id = od.obj_id 
                    join ilias.crs_items citem on citem.obj_id = oref.ref_id
                    where oref.deleted is null and od.`type`='grp' and citem.parent_id = '".$ref_id."'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        return $data;
    }

    protected function isCourse($ref_id){
        global $ilDB;

        $data = array();
        $query = "select od.title
                    from ilias.object_data as od 
                    join ilias.object_reference as oref on oref.obj_id = od.obj_id
                    where od.type = 'crs' and oref.ref_id = '".$ref_id."' ";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        if(empty($data)){
            return false;
        }
        return true;

    }
    
    protected function getObjIdFromLinkedObject($ref_id){
        global $ilDB;

        $data = array();
        
        $query = "select  obj_id
        from ilias.object_reference
        where  ref_id = '".$ref_id."' ";
        
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        return $data;
        
        
        
    }

     protected function isAlreadyLinked($folder_ref_id, $objId){
        global $ilDB;

        $data = array();
        
        $query = "select obj_id 
        from ilias.object_reference
        where  ref_id = '".$ref_id."' 
        and    obj_id = '".$folder_ref_id."' 
        and deleted is null";
        
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        if(empty($data)){
            return true;
        }
        return false;
        
        
    }
    

        protected function checkAccess()
    {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("write", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}
