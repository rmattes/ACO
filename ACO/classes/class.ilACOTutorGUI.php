<?php
include_once 'class.ilACOExerciseMemberTableGUI.php';
include_once './Modules/Exercise/classes/class.ilExerciseManagementGUI.php';
include_once "Modules/Exercise/classes/class.ilExSubmission.php";
include_once "Modules/Exercise/classes/class.ilExSubmissionBaseGUI.php";
require_once './Services/Form/classes/class.ilPropertyFormGUI.php';
include_once("./Services/Form/classes/class.ilSelectInputGUI.php");

/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 15.02.2017
 * Time: 12:06
 * @ilCtrl_IsCalledBy ilACOTutorGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilACOTutorGUI: ilObjExerciseGUI, ilExSubmissionFileGUI, ilFileSystemGUI, ilRepositorySearchGUI
 */

class ilACOTutorGUI extends ilExerciseManagementGUI {

    const VIEW_ASSIGNMENT = 1;
    const VIEW_PARTICIPANT = 2;
    const VIEW_GRADES = 3;

    public $assign = array();
    protected $tree;
    protected $lng;
    protected $tabs;
    protected $ilLocator;
    protected $tpl;
    protected $ctrl;
    protected $pl;
    protected $assignment_list;
    protected $selected_assignment;
    protected $group;
    protected $group_marks;
    protected $si;
    protected $groups_si;
    protected $selInputAss;

    /**
     * ilACOTutorGUI constructor.
     */
    public function __construct()
    {
        global $tree, $ilCtrl, $tpl, $ilTabs, $ilLocator, $lng;
        $this->tree = $tree;
        $this->lng = $lng;
        require_once "./Modules/Exercise/classes/class.ilObjExerciseGUI.php";
        $ex_gui =& new ilObjExerciseGUI("", (int) $_GET["ref_id"], true, false);
        $this->exercise=$ex_gui->object;
        $this->tabs = $ilTabs;
        $this->ctrl = $ilCtrl;
        $ilCtrl->saveParameter($this, array("vw", "member_id"));
        $this->tpl = $tpl;
        $this->ilLocator = $ilLocator;
        $this->pl = ilACOPlugin::getInstance();
    }

    protected function prepareOutput() {

        global $ilLocator, $tpl,$lng,$ilCtrl;


        $this->ctrl->setParameterByClass('ilobjexercisegui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilexercisehandlergui', 'ref_id', $_GET['ref_id']);




        $this->ctrl->getRedirectSource();

    $this->tabs->setBackTarget($this->pl->txt('back'), $this->ctrl->getLinkTargetByClass(array(
        'ilrepositorygui',
        'ilExerciseHandlerGUI',
        )));
        $this->setTitleAndIcon();

        $ilLocator->addContextItems($_GET['ref_id']);
        $tpl->setLocator();
    }

    protected function setTitleAndIcon() {
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_exc.svg'));
        $this->tpl->setTitle($this->pl->txt('obj_extu'));
        $this->tpl->setDescription($this->pl->txt('obj_extu_desc'));
    }

    /**
     *
     */
    public function executeCommand() {
        global $ilCtrl,$ilTabs,$lng;
        $this->checkAccess();
        $cmd = $this->ctrl->getCmd('view');
        $this->ctrl->saveParameter($this, 'ref_id');
        $this->prepareOutput();

        switch ($cmd) {
            case 'view':
                $this->view();
                break;
            default:
                $this->assignment=$this->getAssignment($_GET["ass_id"]);
                $this->group = $_POST["grp_id"];
                $class = $ilCtrl->getNextClass($this);
                $cmd = $ilCtrl->getCmd("listPublicSubmissions");
                switch($class)
                {
                    case "ilfilesystemgui":
                        $ilTabs->clearTargets();
                        $ilTabs->setBackTarget($lng->txt("back"),
                            $ilCtrl->getLinkTarget($this, $this->getViewBack()));

                        ilUtil::sendInfo($lng->txt("exc_fb_tutor_info"));

                        include_once("./Modules/Exercise/classes/class.ilFSStorageExercise.php");
                        $fstorage = new ilFSStorageExercise($this->exercise->getId(), $this->assignment->getId());
                        $fstorage->create();

                        $submission = new ilExSubmission($this->assignment, (int)$_GET["member_id"]);
                        $feedback_id = $submission->getFeedbackId();
                        $noti_rec_ids = $submission->getUserIds();

                        include_once("./Services/User/classes/class.ilUserUtil.php");
                        $fs_title = array();
                        foreach($noti_rec_ids as $rec_id)
                        {
                            $fs_title[] = ilUserUtil::getNamePresentation($rec_id, false, false, "", true);
                        }
                        $fs_title = implode(" / ", $fs_title);

                        include_once("./Services/FileSystem/classes/class.ilFileSystemGUI.php");
                        $fs_gui = new ilFileSystemGUI($fstorage->getFeedbackPath($feedback_id));
                        $fs_gui->setTableId("excfbfil".$this->assignment->getId()."_".$feedback_id);
                        $fs_gui->setAllowDirectories(false);
                        $fs_gui->setTitle($lng->txt("exc_fb_files")." - ".
                            $this->assignment->getTitle()." - ".
                            $fs_title);
                        $pcommand = $fs_gui->getLastPerformedCommand();
                        if (is_array($pcommand) && $pcommand["cmd"] == "create_file")
                        {
                            $this->exercise->sendFeedbackFileNotification($pcommand["name"],
                                $noti_rec_ids, $this->assignment->getId());
                        }
                        $this->ctrl->forwardCommand($fs_gui);
                        break;

                    case 'ilrepositorysearchgui':
                        include_once('./Services/Search/classes/class.ilRepositorySearchGUI.php');
                        $rep_search = new ilRepositorySearchGUI();
                        $rep_search->setTitle($this->lng->txt("exc_add_participant"));
                        $rep_search->setCallback($this,'addMembersObject');

                        // Set tabs
                        $this->ctrl->setReturn($this,'members');

                        $this->ctrl->forwardCommand($rep_search);
                        break;

                    case "ilexsubmissionteamgui":
                        include_once "Modules/Exercise/classes/class.ilExSubmissionTeamGUI.php";
                        $gui = new ilExSubmissionTeamGUI($this->exercise, $this->initSubmission());
                        $ilCtrl->forwardCommand($gui);
                        break;

                    case "ilexsubmissionfilegui":
                        include_once "Modules/Exercise/classes/class.ilExSubmissionFileGUI.php";
                        $gui = new ilExSubmissionFileGUI($this->exercise, $this->initSubmission());
                        $ilCtrl->forwardCommand($gui);
                        break;

                    case "ilexsubmissiontextgui":
                        include_once "Modules/Exercise/classes/class.ilExSubmissionTextGUI.php";
                        $gui = new ilExSubmissionTextGUI($this->exercise, $this->initSubmission());
                        $ilCtrl->forwardCommand($gui);
                        break;

                    case "ilexpeerreviewgui":
                        include_once "Modules/Exercise/classes/class.ilExPeerReviewGUI.php";
                        $gui = new ilExPeerReviewGUI($this->assignment, $this->initSubmission());
                        $ilCtrl->forwardCommand($gui);
                        break;

                    default:
                        $this->{$cmd."Object"}();
                        break;
                }
        }

        $this->tpl->getStandardTemplate();
        $this->tpl->show();
    }

    protected function initSubmission()
    {
        $this->tabs_gui = $this->tabs;
        $back_cmd = $this->getViewBack();
        $this->ctrl->setReturn($this, $back_cmd);
        $this->tabs_gui->setBackTarget($this->lng->txt("back"),
            $this->ctrl->getLinkTarget($this, $back_cmd));

        include_once "Modules/Exercise/classes/class.ilExSubmission.php";
        return new ilExSubmission($this->assignment, $_REQUEST["member_id"], null, true);
    }



    /**
     * default command
     */
    protected function view() {
        $this->membersObject();

    }

    protected function getAssignment($ass_id){
        $ass = ilExAssignment::getInstancesByExercise($this->exercise->getId());

        foreach ($ass as $as){
            if ($as->getID()== $ass_id){
                return $as;
            }
        }
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

    protected function getGroups(){
        global $ilUser, $ilDB;
        $user_id = $ilUser->getId();

        $ref_id = $_GET['ref_id'];

        do {
            $parent_id = $this->getParentIds($ref_id);
            $ref_id = $parent_id[0];
        }while (!$this->isCourse($ref_id));


        $data = array();
        $query = "select od.title, od.obj_id
                    from ilias.object_data as od
                    join ilias.object_reference as oref on oref.obj_id = od.obj_id
                    join ilias.crs_items citem on citem.obj_id = oref.ref_id
                    join ilias.obj_members as om on om.obj_id = oref.obj_id 
                    where oref.deleted is null and od.`type`='grp' and citem.parent_id = '".$ref_id."' and om.usr_id = '".$user_id."' and om.admin = 1 ";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }
        $output = array();
        foreach ($data as $result){

            $output[$result['obj_id']]= $result['title'];
        }

        return $output;
    }




    public function membersObject(){
        global $tpl, $ilCtrl,$ilToolbar, $lng;

        require_once "./Modules/Exercise/classes/class.ilObjExerciseGUI.php";
        $ex_gui =& new ilObjExerciseGUI("", (int) $_GET["ref_id"], true, false);
        $this->exercise=$ex_gui->object;

        $this->group = $_POST["grp_id"];
        var_dump($_GET["grp_id"]);
        var_dump($_POST["grp_id"]);
        $group_options = $this->getGroups();

        include_once 'Services/Tracking/classes/class.ilLPMarks.php';


        $this->si = new ilSelectInputGUI($this->lng->txt(""), "grp_id");
        $this->si->setOptions($group_options);
        if (!empty($this->group)) {
            $this->si->setValue($this->group);
        }else{
            $this->si->setValue(reset($group_options));
            $this->group = reset($group_options);
        }
        $ilToolbar->addStickyItem($this->si);

        // assignment selection
        include_once("./Modules/Exercise/classes/class.ilExAssignment.php");
        $ass = ilExAssignment::getInstancesByExercise($this->exercise->getId());
        $this->assignment_list = $ass;


        if (!$this->assignment)
        {
            $this->assignment = reset($ass);
        }

        if (count($ass) > 1)
        {
            $options = array();
            foreach ($ass as $a)
            {
                $options[$a->getId()] = $a->getTitle();
            }
            include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
            $this->selInputAss = new ilSelectInputGUI($this->lng->txt(""), "ass_id");
            $this->selInputAss->setOptions($options);
            $this->selInputAss->setValue($this->assignment->getId());
            $ilToolbar->addStickyItem($this->selInputAss);

            include_once("./Services/UIComponent/Button/classes/class.ilSubmitButton.php");
            $button = ilSubmitButton::getInstance();
            $button->setCaption($this->pl->txt("exc_select_ass_grp"));
            $button->setCommand("selectAssignment");
            $ilToolbar->addStickyItem($button);

            $ilToolbar->addSeparator();
        }
        // #16165 - if only 1 assignment dropdown is not displayed;
        else if($this->assignment)
        {
            $ilCtrl->setParameter($this, "ass_id", $this->assignment->getId());
        }

        // #16168 - no assignments
        if (count($ass) > 0)
        {

            // we do not want the ilRepositorySearchGUI form action
            $ilToolbar->setFormAction($ilCtrl->getFormAction($this));

            $ilCtrl->setParameter($this, "ass_id", $this->assignment->getId());

//            if(ilExSubmission::hasAnySubmissions($this->assignment->getId()))
//            {
//
//                if($this->assignment->getType() == ilExAssignment::TYPE_TEXT)
//                {
//                    $ilToolbar->addFormButton($lng->txt("exc_list_text_assignment"), "listTextAssignment");
//                }
//                else
//                {
//                    $ilToolbar->addFormButton($lng->txt("download_all_returned_files"), "downloadAll");
//                }
//            }
            $this->ctrl->setParameter($this, "vw", self::VIEW_ASSIGNMENT);

            include_once("./Modules/Exercise/classes/class.ilExerciseMemberTableGUI.php");
            $exc_tab = new ilACOExerciseMemberTableGUI($this, "members", $this->exercise, $this->assignment, $this->group);
            $tpl->setContent($exc_tab->getHTML());
        }
        else
        {
            ilUtil::sendInfo($lng->txt("exc_no_assignments_available"));
        }

        $ilCtrl->setParameter($this, "ass_id", "");
        return;
    }

    function downloadAllObject()
    {
        global $ilCtrl;
        $members = array();

        $this->assignment = $this->getAssignment($_POST['ass_id']);
        $this->group = $_POST["grp_id"];

        foreach($this->exercise->members_obj->getMembers() as $member_id)
        {

            var_dump($member_id);
            var_dump($this->group);
            if($this->isGroupMember($member_id,$this->group)) {
                $submission = new ilExSubmission($this->assignment, $member_id);
                $submission->updateTutorDownloadTime();

                // get member object (ilObjUser)
                if (ilObject::_exists($member_id)) {
                    // adding file metadata
                    foreach ($submission->getFiles() as $file) {
                        $members[$file["user_id"]]["files"][$file["returned_id"]] = $file;
                    }

                    $tmp_obj =& ilObjectFactory::getInstanceByObjId($member_id);
                    $members[$member_id]["name"] = $tmp_obj->getFirstname() . " " . $tmp_obj->getLastname();
                    unset($tmp_obj);
                }
            }else{
                $grps = $this->getGroups();
                $grp_title = $grps[$this->group];
                ilUtil::sendFailure($this->pl->txt("exc_no_submission_in_group")." ".$grp_title,true);
                $ilCtrl->redirect($this, $this->getViewBack());
            }
        }


        ilExSubmission::downloadAllAssignmentFiles($this->assignment, $members);
    }
    protected function isGroupMember($member,$group_id){
        global $ilDB;


        $data= array();
        $query = "select om.usr_id
        from ilias.obj_members as om
        where om.obj_id = '".$group_id."' and om.usr_id = '".$member."'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)){
            array_push($data,$record);
        }

        if(empty($data)){
            return false;
        }

        return true;

    }

    function saveStatusAllObject()
    {

        $user_ids = array();
        $data = array();
        foreach(array_keys($_POST["id"]) as $user_id)
        {
            array_push($user_ids,$user_id);
            $data[-1][$user_id] = array(
                "status" => ilUtil::stripSlashes($_POST["status"][$user_id])
            ,"notice" => ilUtil::stripSlashes($_POST["notice"][$user_id])
            ,"mark" => ilUtil::stripSlashes($_POST["mark"][$user_id])
            );

        }

        $this->saveStatus($data,$user_ids);

    }


    protected function saveStatus(array $a_data,$user_ids = '')
    {
        global $ilCtrl;

        include_once("./Modules/Exercise/classes/class.ilExAssignment.php");

        $saved_for = array();



        foreach($a_data as $ass_id => $users)
        {
            $ass = ($ass_id < 0)
                ? $this->assignment
                : new ilExAssignment($ass_id);


            foreach($users as $user_id => $values)
            {
                // this will add team members if available
                $submission = new ilExSubmission($ass, $user_id);
                foreach($submission->getUserIds() as $sub_user_id)
                {
                    $uname = ilObjUser::_lookupName($sub_user_id);
                    $saved_for[$sub_user_id] = $uname["lastname"].", ".$uname["firstname"];

                    $member_status = $ass->getMemberStatus($sub_user_id);
                    $member_status->setStatus($values["status"]);
                    $member_status->setNotice($values["notice"]);
                    $member_status->setMark($values["mark"]);
                    $member_status->update();
                }
            }
        }

        if (count($saved_for) > 0)
        {
            $save_for_str = "(".implode($saved_for, " - ").")";
        }


        if(!empty($user_ids)){
            global $ilDB;
            $ass = ilExAssignment::getInstancesByExercise($this->exercise->getId());
            $exercise_id = $this->exercise->getId();
            $ass_ids = array();
            foreach ($ass as $assignment){
                array_push($ass_ids,$assignment->getId());
            }

            foreach ($user_ids as $usr_id) {
                $data = array();
                $query = 'SELECT ilias.exc_mem_ass_status.mark from ilias.exc_mem_ass_status where ilias.exc_mem_ass_status.ass_id in (' . implode(",", $ass_ids) . ') and ilias.exc_mem_ass_status.usr_id = '.$usr_id.'';
                $res = $ilDB->query($query);
                while ($record = $ilDB->fetchAssoc($res)){
                    array_push($data,$record);
                }
                $totalmark=0;
                foreach ($data as $ass_mark){
                    $totalmark+=$ass_mark['mark'];
                }
                $query='UPDATE ilias.ut_lp_marks
                    SET  ilias.ut_lp_marks.mark = '.$totalmark.'
                    WHERE ilias.ut_lp_marks.obj_id = '.$exercise_id.' AND ilias.ut_lp_marks.usr_id = '.$usr_id.'';
                $ilDB->manipulate($query);
            }
        }
        ilUtil::sendSuccess($this->lng->txt("exc_status_saved")." ".$save_for_str, true);
        $ilCtrl->redirect($this, $this->getViewBack());
    }


    public function selectAssignmentObject(){

            global $ilTabs;

        $_GET["grp_id"] = ilUtil::stripSlashes($_POST["grp_id"]);
        $this->group = ilUtil::stripSlashes($_POST["grp_id"]);

        $_GET["ass_id"] = ilUtil::stripSlashes($_POST["ass_id"]);
        $this->selected_assignment = ilUtil::stripSlashes($_POST["ass_id"]);

        $ass = ilExAssignment::getInstancesByExercise($this->exercise->getId());

        foreach ($ass as $as){
          if ($as->getID()== $this->selected_assignment){
               $this->assignment = $as;
               $this->assign[0] = $as;
          }
          }
        $this->membersObject();

    }

    protected function checkAccess() {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("write", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}

