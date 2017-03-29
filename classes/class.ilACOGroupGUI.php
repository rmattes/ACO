<?php
include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
include_once("./Services/UIComponent/Explorer2/classes/class.ilExplorerBaseGUI.php");
require_once './Services/Form/classes/class.ilPropertyFormGUI.php';
require_once './Modules/Group/classes/class.ilObjGroup.php';
require_once './Services/Object/classes/class.ilObject2.php';
require_once './Services/Form/classes/class.ilNumberInputGUI.php';
require_once './Services/Form/classes/class.ilTextInputGUI.php';
require_once './Services/Form/classes/class.ilRadioGroupInputGUI.php';
require_once './Services/Form/classes/class.ilRadioOption.php';
require_once './Services/Form/classes/class.ilDateTimeInputGUI.php';
require_once './Modules/Folder/classes/class.ilObjFolder.php';

/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 05.12.2016
 * Time: 15:54
 * @ilCtrl_IsCalledBy ilACOGroupGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilACOGroupGUI: ilObjCourseAdministrationGUI
 * 
 *  This class implements the functionality of the "groupcreator tab" which are
 *  the number of groups (checks the db and numbers the group consecutively), 
 *  maximum members, how to join the group (password ord not), from/till which
 *  date and if you want a folder with a unique name in every created group.   
 *  
 */
class ilACOGroupGUI
{
    const CREATION_SUCCEEDED = 'creation_succeeded';
    const CREATION_FAILED = 'creation_failed';
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
    /**
     * @var ilTree
     */
    protected $tree;
    

    protected $courses;
    protected $members;
    protected $group_count;
    protected $number_grp;
    protected $reg_proc;
    protected $pass;
    
    protected $group_time_start;
    
    protected $group_name;

    protected $group_folder_name;
    protected $group_folder_name_checkbox;
    
      


    public function __construct() {
        global $tree, $ilCtrl, $tpl, $ilTabs, $ilLocator, $lng;
        $this->tree = $tree;
        $this->lng = $lng;
        $this->tabs = $ilTabs;
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->ilLocator = $ilLocator;
        $this->pl = ilACOPlugin::getInstance();
    }

    protected function prepareOutput() {

        global $ilLocator, $tpl;

        $this->ctrl->setParameterByClass('ilobjcourseadministrationgui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilacogroupdisplaygui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilacomembergui','ref_id',$_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilrepositorygui', 'ref_id', $_GET['ref_id']);

        $this->tabs->addTab('course_management', $this->pl->txt('tab_course_management'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilACOGroupGUI')));

        $this->tabs->addSubTab('group_create',$this->pl->txt('group_create'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilACOGroupGUI')));
        $this->tabs->addSubTab('course_edit',$this->pl->txt('course_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilACOGroupDisplayGUI')));
        $this->tabs->addSubTab('member_edit',$this->pl->txt('member_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilACOMemberGUI')));
        $this->tabs->activateSubTab('group_create');

        $this->ctrl->getRedirectSource();

        $this->tabs->setBackTarget($this->pl->txt('back'), $this->ctrl->getLinkTargetByClass(array(
            'ilrepositorygui',
            'ilrepositorygui',
        )));
        $this->setTitleAndIcon();

        $ilLocator->addRepositoryItems($_GET['ref_id']);
        $tpl->setLocator();
    }

    protected function setTitleAndIcon() {
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_crs.svg'));
        $this->tpl->setTitle($this->pl->txt('obj_acop'));
        $this->tpl->setDescription($this->pl->txt('obj_acop_desc'));
    }


    /**
     *
     */
    public function executeCommand() {
        $this->checkAccess();
        $cmd = $this->ctrl->getCmd('view');
        $this->ctrl->saveParameter($this, 'ref_id');
        $this->prepareOutput();

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

    protected function initForm(){
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->pl->txt('group_create_title'));
        $form->setId('group_create');
        $form->setFormAction($this->ctrl->getFormAction($this));
        
        $this->group_name = new ilTextInputGUI($this->pl->txt("group_name"), "group_name");
        $this->group_name->setRequired(true);
        $form->addItem($this->group_name);

        $this->group_count = new ilNumberInputGUI($this->pl->txt('group_count'), 'group_count');
        $this->members = new ilNumberInputGUI($this->pl->txt('members'), 'members');

        $this->group_count->setRequired(true);
        $this->members->setRequired(true);

        $form->addItem($this->group_count);
        $form->addItem($this->members);
        $this->reg_proc = new ilRadioGroupInputGUI($this->pl->txt('grp_registration_type'),'subscription_type');

        $opt = new ilRadioOption($this->pl->txt('grp_reg_direct_info_screen'),GRP_REGISTRATION_DIRECT);
        $this->reg_proc->addOption($opt);

        $opt = new ilRadioOption($this->pl->txt('grp_reg_passwd_info_screen'),GRP_REGISTRATION_PASSWORD);
        $this->pass = new ilTextInputGUI($this->pl->txt("password"),'subscription_password');
        $this->pass->setSize(12);
        $this->pass->setMaxLength(12);

        $opt->addSubItem($this->pass);
        $this->reg_proc->addOption($opt);
        $form->addItem($this->reg_proc);

        $time_limit = new ilCheckboxInputGUI($this->pl->txt('grp_reg_limited'),'reg_limit_time');
        $this->lng->loadLanguageModule('dateplaner');
        include_once './Services/Form/classes/class.ilDateDurationInputGUI.php';
        $this->tpl->addJavaScript('./Services/Form/js/date_duration.js');
        $dur = new ilDateDurationInputGUI($this->pl->txt('grp_reg_period'),'reg');
        $dur->setStartText($this->pl->txt('cal_start'));
        $dur->setEndText($this->pl->txt('cal_end'));
        $dur->setShowTime(true);

        $time_limit->addSubItem($dur);
        $form->addItem($time_limit);

        $this->group_folder_name_checkbox = new ilCheckboxInputGUI($this->pl->txt('group_folder_name_checkbox'),'group_folder_name_checkbox');
        $this->group_folder_name = new ilTextInputGUI($this->pl->txt("group_folder_name"),"group_folder_name");
        $this->group_folder_name->setInfo($this->pl->txt("info_group_folder"));
        $this->group_folder_name_checkbox->addSubItem($this->group_folder_name);
        $form->addItem($this->group_folder_name_checkbox);


        $form->addCommandButton('createGroups', $this->pl->txt('create_groups'));
        
               
        
        return $form;
    }

    protected function loadDate($a_field)
    {

        global $ilUser;

        include_once('./Services/Calendar/classes/class.ilDateTime.php');

        $dt = $_POST['reg'][$a_field];

        $date = new ilDateTime($dt,IL_CAL_DATE,$ilUser->getTimeZone());
        return $date;
    }
    
    protected function countGroups($parent_id){
        
        global $ilDB;
        
        $group_number = array();
        
         $query = "select od.title as 'Übungsruppe'
                  from ilias.object_data od
                  join ilias.object_reference obr on od.obj_id = obr.obj_id
                  join ilias.tree crsi on obr.ref_id = crsi.child
                  where (od.type = 'grp') and (obr.deleted is null) and (crsi.parent = '".$parent_id."') ";
        
           $results = $ilDB->query($query);
            while ($record = $ilDB->fetchAssoc($results)){
                   array_push($group_number,$record);
            }
        $result = count($group_number);
        
        return $result;
         
    }


    protected function createGroups()
    {
        
        global $ilDB, $ilUser, $rbacadmin;
        
        $userID = $ilUser->getId();
        $form = $this->initForm();
        $form->setValuesByPost();
        var_dump($this->loadDate('start'));
        //var_dump($this->loadDate('end'));
        $reg_start = $this->loadDate('start');
        $reg_end = $this->loadDate('end');
        $created = false;
        $prefix = $this->group_name->getValue();
        $number = $this->group_count->getValue();
        $members = $this->members->getValue();
        
               
        $password = $this->pass->getValue();
        $reg_type = $this->reg_proc->getValue();
        $folder_title = explode(";", $this->group_folder_name->getValue());



        foreach ($folder_title as $forCourseFolderTitle){

            if ($_POST['group_folder_name_checkbox'] AND !($this->folderAlreadyExistingCourse($forCourseFolderTitle))){

                 $courseFolder = new ilObjFolder();
                 $courseFolder->setTitle($forCourseFolderTitle);
                 $courseFolder->create();
                 $courseFolder->createReference();
                 $courseFolder->putInTree($_GET['ref_id']);
                 $courseFolder->setPermissions($_GET['ref_id']);

            }

        }

        foreach ($this->groupsInCourse() as $groupsInCourse){

            //adminFolder is created in every existing group which hasn't had such a folder yet
            foreach ($folder_title as $forGroupFolderTitle) {

                //var_dump($forGroupFolderTitle);

                if ($_POST['group_folder_name_checkbox'] AND
                        !($this->folderAlreadyExistingGroup($forGroupFolderTitle, $groupsInCourse['ref_id']))){

                    $oldGroupFolder = new ilObjFolder();
                    $oldGroupFolder->setTitle($forGroupFolderTitle);
                    $oldGroupFolder->create();
                    $oldGroupFolder->createReference();
                    $oldGroupFolder->putInTree($groupsInCourse['ref_id']);
                    $oldGroupFolder->setPermissions($groupsInCourse['ref_id']);
                }
            }

        }
       
        $parent_id = $_GET['ref_id']; 
        
        $result = $this->countGroups($parent_id);

        $nn = 1;
        
        if ($result > 0){
            $result ++;
            $nn = $result;
            $number = $number + $nn - 1;
        }


        for ($n = $nn ; $n <= $number; $n++) {
                $group = new ilObjGroup();

                         
                if($n<10){   //is necessary for numerical sort
                    
                $group->setTitle($prefix.' 0'.$n);
                
                }
                
                else
                {
                     $group->setTitle($prefix.' '.$n);
                }
                $group->setGroupType(GRP_TYPE_CLOSED);
                $group->setRegistrationType($reg_type);
                if($reg_type == GRP_REGISTRATION_PASSWORD){
                    $group->setPassword($password);
                }
                $group->enableUnlimitedRegistration((bool) !$_POST['reg_limit_time']);
                if($reg_end<$reg_start){
                
                $reg_end = $reg_start;
                }
              
                $group->setRegistrationStart($reg_start);
                $group->setRegistrationEnd($reg_end);
                $group->setMaxMembers($members);
                $group->enableMembershipLimitation(true);
                $group->create();
                $group->createReference();
                $group->putInTree($_GET['ref_id']);
                $group->setPermissions($_GET['ref_id']);
                $admin_role = $group->getDefaultAdminRole();
                $rbacadmin->assignUser($admin_role,$userID);

                $query = "UPDATE ilias.obj_members as om
                    SET  om.contact = 1, om.notification = 1
                    WHERE om.obj_id = '".$group->getId()."' AND om.usr_id = '".$userID."' ";
                 $ilDB->manipulate($query);



                foreach ($folder_title as $forGroupFolderTitle) {

                    if ($_POST['group_folder_name_checkbox']){

                        $groupFolder = new ilObjFolder();
                        $groupFolder->setTitle($forGroupFolderTitle);
                        $groupFolder->create();
                        $groupFolder->createReference();
                        $groupFolder->putInTree($group->getRefId());
                        $groupFolder->setPermissions($group->getRefId());
                    }
                }

                $this->courses['created'] .= ilObject2::_lookupTitle(ilObject2::_lookupObjId($_GET['ref_id'])) . ' - ' . $group->getTitle() . '<br>';
                $created = true;
        }
            if($created) {
                ilUtil::sendSuccess(sprintf($this->pl->txt(self::CREATION_SUCCEEDED), $this->courses['created'], $this->courses['updated'], $this->courses['refs'], $this->courses['refs_del']));
                $form = $this->initForm();
                $this->tpl->setContent($form->getHTML());
                
             
            }else {
                ilUtil::sendFailure($this->pl->txt(self::CREATION_FAILED), true);
                $this->tpl->setContent($form->getHTML());

            }
    }

    protected function groupsInCourse(){
        global $ilDB;
        $group_id= array();

        $query = "select oref.ref_id from ilias.crs_items as citem
                  join ilias.object_reference as oref on oref.ref_id = citem.obj_id
                  join ilias.object_data as od on oref.obj_id = od.obj_id                  
                  join ilias.crs_items as ci on oref.ref_id = ci.obj_id
                  where od.type='grp' and ci.parent_id='".$_GET['ref_id']."' and oref.deleted is null";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($group_id,$record);
        }

        return $group_id;
    }

    protected function folderAlreadyExistingCourse($folder_name){
        global $ilDB;
        $folderCourse = array();

        //check if the folder already exists in the course
        $query = "select COUNT(*) from ilias.crs_items as citem
                  join ilias.object_reference as oref on oref.ref_id = citem.obj_id
                  join ilias.object_data as od on oref.obj_id = od.obj_id                  
                  join ilias.crs_items as ci on oref.ref_id = ci.obj_id
                  where od.type='fold' and ci.parent_id='".$_GET['ref_id']."' and od.title='".$folder_name."' and oref.deleted is null";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($folderCourse,$record);
        }

        //if folder already exists in the course
        if (strcmp($folderCourse[0]["COUNT(*)"], "0") !== 0) {
          
            return true;
        } else {
            return false;
        }

    }

    protected function folderAlreadyExistingGroup($folder_name,$group_ref_id){
        global $ilDB;
        $folderGroup = array();

            //check if folder exists in a group
            $query = "select COUNT(*) from ilias.crs_items as citem
                  join ilias.object_reference as oref on oref.ref_id = citem.obj_id
                  join ilias.object_data as od on oref.obj_id = od.obj_id                  
                  join ilias.crs_items as ci on oref.ref_id = ci.obj_id
                  where od.type='fold' and ci.parent_id='".$group_ref_id."' and od.title='".$folder_name."' and oref.deleted is null";
            $result = $ilDB->query($query);
            while ($record = $ilDB->fetchAssoc($result)){
                array_push($folderGroup,$record);
            }


        //if the folder already exists in a group
        if (strcmp($folderGroup[0]["COUNT(*)"], "0") !== 0)  {
            
            return true;
        } else {
            return false;
        }
    }


    protected function checkAccess() {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("read", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}
