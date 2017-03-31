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
 * @ilCtrl_Calls      ilACOTutorGUI: ilObjExerciseGUI, ilExSubmissionFileGUI, ilFileSystemGUI, ilRepositorySearchGUI, ilExSubmissionTeamGUI
 *
 * This class implements the functionality of the groupfilter/tutor tab in the excercises.
 * The main use of this class is that you can filter the user submissions by groups
 * if the excercise is linked into the groups
 *
 */
class ilACOTutorGUI
{

    const VIEW_ASSIGNMENT = 1;
    const VIEW_PARTICIPANT = 2;
    const VIEW_GRADES = 3;

    protected $exercise;
    protected $assignment;

    public $assign = array();
    protected $tree;
    protected $lng;
    protected $tabs;
    protected $ilLocator;
    protected $tpl;
    protected $ctrl;
    protected $pl;
    protected $selected_assignment;
    protected $group;

    /**
     * ilACOTutorGUI constructor.
     */
    public function __construct()
    {
        global $tree, $ilCtrl, $tpl, $ilTabs, $ilLocator, $lng;
        $this->tree = $tree;
        $this->lng = $lng;
        require_once "./Modules/Exercise/classes/class.ilObjExerciseGUI.php";
        $ex_gui =& new ilObjExerciseGUI("", (int)$_GET["ref_id"], true, false);
        $this->exercise = $ex_gui->object;
        $this->tabs = $ilTabs;
        $this->ctrl = $ilCtrl;
        $ilCtrl->saveParameter($this, array("vw", "member_id"));
        $this->tpl = $tpl;
        $this->ilLocator = $ilLocator;
        $this->pl = ilACOPlugin::getInstance();
    }

    protected function prepareOutput()
    {

        global $ilLocator, $tpl, $lng, $ilCtrl;

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

    protected function setTitleAndIcon()
    {
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_exc.svg'));
        $this->tpl->setTitle($this->pl->txt('obj_extu'));
        $this->tpl->setDescription($this->pl->txt('obj_extu_desc'));
    }

    /**
     *
     */
    public function executeCommand()
    {
        global $ilCtrl, $ilTabs, $lng;
        $this->checkAccess();
        $cmd = $this->ctrl->getCmd('view');
        $this->ctrl->saveParameter($this, 'ref_id');
        $this->prepareOutput();

        switch ($cmd) {
            case 'view':
                $this->view();
                break;
            default:
                $this->assignment = $this->getAssignment($_GET["ass_id"]);
                $this->group = $_GET["grp_id"];
                $class = $ilCtrl->getNextClass($this);
                $cmd = $ilCtrl->getCmd("listPublicSubmissions");
                switch ($class) {
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
                        foreach ($noti_rec_ids as $rec_id) {
                            $fs_title[] = ilUserUtil::getNamePresentation($rec_id, false, false, "", true);
                        }
                        $fs_title = implode(" / ", $fs_title);

                        include_once("./Services/FileSystem/classes/class.ilFileSystemGUI.php");
                        $fs_gui = new ilFileSystemGUI($fstorage->getFeedbackPath($feedback_id));
                        $fs_gui->setTableId("excfbfil" . $this->assignment->getId() . "_" . $feedback_id);
                        $fs_gui->setAllowDirectories(false);
                        $fs_gui->setTitle($lng->txt("exc_fb_files") . " - " .
                            $this->assignment->getTitle() . " - " .
                            $fs_title);
                        $pcommand = $fs_gui->getLastPerformedCommand();
                        if (is_array($pcommand) && $pcommand["cmd"] == "create_file") {
                            $this->exercise->sendFeedbackFileNotification($pcommand["name"],
                                $noti_rec_ids, $this->assignment->getId());
                        }
                        $this->ctrl->forwardCommand($fs_gui);
                        break;

                    case 'ilrepositorysearchgui':
                        include_once('./Services/Search/classes/class.ilRepositorySearchGUI.php');
                        $rep_search = new ilRepositorySearchGUI();
                        $rep_search->setTitle($this->lng->txt("exc_add_participant"));
                        $rep_search->setCallback($this, 'addMembersObject');

                        // Set tabs
                        $this->ctrl->setReturn($this, 'members');

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
                        $this->{$cmd . "Object"}();
                        break;
                }
        }

        $this->tpl->getStandardTemplate();
        $this->tpl->show();
    }

    protected function getViewBack()
    {
        switch ($_REQUEST["vw"]) {
            case self::VIEW_PARTICIPANT:
                $back_cmd = "showParticipant";
                break;

            case self::VIEW_GRADES:
                $back_cmd = "showGradesOverview";
                break;

            default:
                $back_cmd = "members";
                break;
        }
        return $back_cmd;
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
    protected function view()
    {
        $this->membersObject();

    }

    protected function getAssignment($ass_id)
    {
        $ass = ilExAssignment::getInstancesByExercise($this->exercise->getId());

        foreach ($ass as $as) {
            if ($as->getID() == $ass_id) {
                return $as;
            }
        }
    }

    protected function isCourse($ref_id)
    {
        global $ilDB;

        $data = array();
        $query = "select od.title
                    from ilias.object_data as od 
                    join ilias.object_reference as oref on oref.obj_id = od.obj_id
                    where od.type = 'crs' and oref.ref_id = '" . $ref_id . "' ";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)) {
            array_push($data, $record);
        }
        if (empty($data)) {
            return false;
        }
        return true;

    }

    protected function getParentIds($id)
    {

        global $ilDB;

        $ids = array();
        $data = array();
        $query = "select tree.parent from ilias.tree as tree where child = '" . $id . "'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)) {
            array_push($data, $record);
        }
        foreach ($data as $folder) {
            array_push($ids, $folder['parent']);
        }
        return $ids;

    }

    protected function getGroups()
    {
        global $ilUser, $ilDB;
        $user_id = $ilUser->getId();

        $ref_id = $_GET['ref_id'];

        do {
            $parent_id = $this->getParentIds($ref_id);
            $ref_id = $parent_id[0];
        } while (!$this->isCourse($ref_id));


        $data = array();
        $query = "select od.title, od.obj_id
                    from ilias.object_data as od
                    join ilias.object_reference as oref on oref.obj_id = od.obj_id
                    join ilias.crs_items citem on citem.obj_id = oref.ref_id
                    join ilias.obj_members as om on om.obj_id = oref.obj_id 
                    where oref.deleted is null and od.`type`='grp' and citem.parent_id = '" . $ref_id . "' and om.usr_id = '" . $user_id . "' and om.admin = 1 ";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)) {
            array_push($data, $record);
        }
        $output = array();
        foreach ($data as $result) {

            $output[$result['obj_id']] = $result['title'];
        }

        return $output;
    }


    public function membersObject()
    {
        global $tpl, $ilCtrl, $ilToolbar, $lng;

        require_once "./Modules/Exercise/classes/class.ilObjExerciseGUI.php";
        $ex_gui =& new ilObjExerciseGUI("", (int)$_GET["ref_id"], true, false);
        $this->exercise = $ex_gui->object;

        $group_options = $this->getGroups();

        include_once 'Services/Tracking/classes/class.ilLPMarks.php';


        $seli = new ilSelectInputGUI($this->lng->txt(""), "grp_id");
        $seli->setOptions($group_options);
        $seli->setValue($_POST["grp_id"]);
        $ilToolbar->addStickyItem($seli);
        $this->group = $seli->getValue();

        // assignment selection
        include_once("./Modules/Exercise/classes/class.ilExAssignment.php");
        $ass = ilExAssignment::getInstancesByExercise($this->exercise->getId());


        if (!$this->assignment) {
            $this->assignment = current($ass);
        }

        reset($ass);

        if (count($ass) > 1) {
            $options = array();
            foreach ($ass as $a) {
                $options[$a->getId()] = $a->getTitle();
            }
            include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
            $si = new ilSelectInputGUI($this->lng->txt(""), "ass_id");
            $si->setOptions($options);
            $si->setValue($this->assignment->getId());
            $ilToolbar->addStickyItem($si);

            include_once("./Services/UIComponent/Button/classes/class.ilSubmitButton.php");
            $button = ilSubmitButton::getInstance();
            $button->setCaption($this->pl->txt("exc_select_ass_grp"));
            $button->setCommand("selectAssignment");
            $ilToolbar->addStickyItem($button);

            $ilToolbar->addSeparator();
        } // #16165 - if only 1 assignment dropdown is not displayed;
        else if ($this->assignment) {
            $ilCtrl->setParameter($this, "ass_id", $this->assignment->getId());
            include_once("./Services/UIComponent/Button/classes/class.ilSubmitButton.php");
            $button = ilSubmitButton::getInstance();
            $button->setCaption($this->pl->txt("exc_select_ass_grp"));
            $button->setCommand("selectAssignment");
            $ilToolbar->addStickyItem($button);

            $ilToolbar->addSeparator();
        }

        // #16168 - no assignments
        if (count($ass) > 0) {

            // we do not want the ilRepositorySearchGUI form action
            $ilToolbar->setFormAction($ilCtrl->getFormAction($this));

            $ilCtrl->setParameter($this, "ass_id", $this->assignment->getId());

//            if (ilExSubmission::hasAnySubmissions($this->assignment->getId())) {
//
//                if ($this->assignment->getType() == ilExAssignment::TYPE_TEXT) {
//                    $ilToolbar->addFormButton($lng->txt("exc_list_text_assignment"), "listTextAssignment");
//                } else {
//                    $ilToolbar->addFormButton($lng->txt("download_all_returned_files"), "downloadAll");
//                }
//            }
            $this->ctrl->setParameter($this, "vw", self::VIEW_ASSIGNMENT);

            include_once("./Modules/Exercise/classes/class.ilExerciseMemberTableGUI.php");
            $exc_tab = new ilACOExerciseMemberTableGUI($this, "members", $this->exercise, $this->assignment, $this->group);
            $tpl->setContent($exc_tab->getHTML());
        } else {
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

        foreach ($this->exercise->members_obj->getMembers() as $member_id) {

            if ($this->isGroupMember($member_id, $this->group)) {
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
            } else {
                $grps = $this->getGroups();
                $grp_title = $grps[$this->group];
                ilUtil::sendFailure($this->pl->txt("exc_no_submission_in_group") . " " . $grp_title, true);
                $ilCtrl->redirect($this, $this->getViewBack());
            }
        }

        ilExSubmission::downloadAllAssignmentFiles($this->assignment, $members);
    }

    protected function isGroupMember($member, $group_id)
    {
        global $ilDB;


        $data = array();
        $query = "select om.usr_id
        from ilias.obj_members as om
        where om.obj_id = '" . $group_id . "' and om.usr_id = '" . $member . "'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)) {
            array_push($data, $record);
        }

        if (empty($data)) {
            return false;
        }

        return true;

    }

    function saveCommentsObject()
    {
        if (!isset($_POST['comments_value'])) {
            return;
        }

        $this->exercise->members_obj->setNoticeForMember($_GET["member_id"],
            ilUtil::stripSlashes($_POST["comments_value"]));
        ilUtil::sendSuccess($this->lng->txt("exc_members_comments_saved"));
        $this->membersObject();
    }

    /**
     * Save comment for learner (asynch)
     */
    function saveCommentForLearnersObject()
    {
        $res = array("result" => false);

        if ($this->ctrl->isAsynch()) {
            $ass_id = (int)$_POST["ass_id"];
            $user_id = (int)$_POST["mem_id"];
            $comment = trim($_POST["comm"]);

            if ($ass_id && $user_id) {
                $submission = new ilExSubmission($this->assignment, $user_id);
                $user_ids = $submission->getUserIds();

                $all_members = new ilExerciseMembers($this->exercise);
                $all_members = $all_members->getMembers();

                $reci_ids = array();
                foreach ($user_ids as $user_id) {
                    if (in_array($user_id, $all_members)) {
                        $member_status = $this->assignment->getMemberStatus($user_id);
                        $member_status->setComment(ilUtil::stripSlashes($comment));
                        $member_status->update();

                        if (trim($comment)) {
                            $reci_ids[] = $user_id;
                        }
                    }
                }

                if (sizeof($reci_ids)) {
                    // send notification
                    $this->exercise->sendFeedbackFileNotification(null, $reci_ids,
                        $ass_id, true);
                }

                $res = array("result" => true, "snippet" => ilUtil::shortenText($comment, 25, true));
            }
        }

        echo(json_encode($res));
        exit();
    }


    function createTeamsObject()
    {
        global $ilCtrl;

        $members = $this->getMultiActionUserIds(true);
        if ($members) {
            $new_members = array();

            include_once "Modules/Exercise/classes/class.ilExAssignmentTeam.php";
            foreach ($members as $group) {
                if (is_array($group)) {
                    $new_members = array_merge($new_members, $group);

                    $first_user = $group;
                    $first_user = array_shift($first_user);
                    $team = ilExAssignmentTeam::getInstanceByUserId($this->assignment->getId(), $first_user);
                    foreach ($group as $user_id) {
                        $team->removeTeamMember($user_id);
                    }
                } else {
                    $new_members[] = $group;
                }
            }

            if (sizeof($new_members)) {
                // see ilExSubmissionTeamGUI::addTeamMemberActionObject()
                $first_user = array_shift($new_members);
                $team = ilExAssignmentTeam::getInstanceByUserId($this->assignment->getId(), $first_user, true);
                if (sizeof($new_members)) {
                    foreach ($new_members as $user_id) {
                        $team->addTeamMember($user_id);
                    }
                }

                // re-evaluate complete team, as some members might have had submitted
                $submission = new ilExSubmission($this->assignment, $first_user);
                $this->exercise->processExerciseStatus(
                    $this->assignment,
                    $team->getMembers(),
                    $submission->hasSubmitted(),
                    $submission->validatePeerReviews()
                );
            }

            ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
        }
        $ilCtrl->redirect($this, "members");
    }

    function dissolveTeamsObject()
    {
        global $ilCtrl;

        $members = $this->getMultiActionUserIds(true);
        if ($members) {
            include_once "Modules/Exercise/classes/class.ilExAssignmentTeam.php";
            foreach ($members as $group) {
                // if single member - nothing to do
                if (is_array($group)) {
                    // see ilExSubmissionTeamGUI::removeTeamMemberObject()

                    $first_user = $group;
                    $first_user = array_shift($first_user);
                    $team = ilExAssignmentTeam::getInstanceByUserId($this->assignment->getId(), $first_user);
                    foreach ($group as $user_id) {
                        $team->removeTeamMember($user_id);
                    }

                    // reset ex team members, as any submission is not valid without team
                    $this->exercise->processExerciseStatus(
                        $this->assignment,
                        $group,
                        false
                    );
                }
            }

            ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
        }
        $ilCtrl->redirect($this, "members");
    }

    protected function getMultiActionUserIds($a_keep_teams = false)
    {
        if (!is_array($_POST["member"]) ||
            count($_POST["member"]) == 0
        ) {
            ilUtil::sendFailure($this->lng->txt("no_checkbox"), true);
        } else {
            $members = array();
            foreach (array_keys($_POST["member"]) as $user_id) {
                $submission = new ilExSubmission($this->assignment, $user_id);
                $tmembers = $submission->getUserIds();
                if (!(bool)$a_keep_teams) {
                    foreach ($tmembers as $tuser_id) {
                        $members[$tuser_id] = 1;
                    }
                } else {
                    if ($tmembers) {
                        $members[] = $tmembers;
                    } else {
                        // no team yet
                        $members[] = $user_id;
                    }
                }
            }
            return $members;
        }
    }

    /**
     * set feedback status for member and redirect to mail screen
     */
    function redirectFeedbackMailObject()
    {
        $members = array();

        if ($_GET["member_id"] != "") {
            $submission = new ilExSubmission($this->assignment, $_GET["member_id"]);
            $members = $submission->getUserIds();
        } else if ($members = $this->getMultiActionUserIds()) {
            $members = array_keys($members);
        }

        if ($members) {
            $logins = array();
            foreach ($members as $user_id) {
                $member_status = $this->assignment->getMemberStatus($user_id);
                $member_status->setFeedback(true);
                $member_status->update();
                $logins[] = ilObjUser::_lookupLogin($user_id);
            }
            $logins = implode($logins, ",");

            // #16530 - see ilObjCourseGUI::createMailSignature
            $sig = chr(13) . chr(10) . chr(13) . chr(10);
            $sig .= $this->lng->txt('exc_mail_permanent_link');
            $sig .= chr(13) . chr(10) . chr(13) . chr(10);
            include_once './Services/Link/classes/class.ilLink.php';
            $sig .= ilLink::_getLink($this->exercise->getRefId());
            $sig = rawurlencode(base64_encode($sig));

            require_once 'Services/Mail/classes/class.ilMailFormCall.php';
            ilUtil::redirect(ilMailFormCall::getRedirectTarget(
                $this,
                $this->getViewBack(),
                array(),
                array(
                    'type' => 'new',
                    'rcp_to' => $logins,
                    ilMailFormCall::SIGNATURE_KEY => $sig
                )
            ));
        }
        ilUtil::sendFailure($this->lng->txt("no_checkbox"), true);
        $this->ctrl->redirect($this, "members");
    }

    /**
     * Send assignment per mail to participants
     */
    function sendMembersObject()
    {
        global $ilCtrl;

        $members = $this->getMultiActionUserIds();
        if (is_array($members)) {
            $this->exercise->sendAssignment($this->assignment, $members);
            ilUtil::sendSuccess($this->lng->txt("exc_sent"), true);
        }
        $ilCtrl->redirect($this, "members");
    }

    function saveStatusAllObject()
    {

        $this->group = $_POST["grp_id"];
        $_GET["grp_id"] = $_POST["grp_id"];
        $user_ids = array();
        $data = array();
        foreach (array_keys($_POST["id"]) as $user_id) {
            array_push($user_ids, $user_id);
            $data[-1][$user_id] = array(
                "status" => ilUtil::stripSlashes($_POST["status"][$user_id])
            , "notice" => ilUtil::stripSlashes($_POST["notice"][$user_id])
            , "mark" => ilUtil::stripSlashes($_POST["mark"][$user_id])
            );

        }

        $this->saveStatus($data, $user_ids);

    }


    protected function saveStatus(array $a_data, $user_ids = '')
    {
        global $ilCtrl;

        include_once("./Modules/Exercise/classes/class.ilExAssignment.php");

        $saved_for = array();


        foreach ($a_data as $ass_id => $users) {
            $ass = ($ass_id < 0)
                ? $this->assignment
                : new ilExAssignment($ass_id);


            foreach ($users as $user_id => $values) {
                // this will add team members if available
                $submission = new ilExSubmission($ass, $user_id);
                foreach ($submission->getUserIds() as $sub_user_id) {
                    $uname = ilObjUser::_lookupName($sub_user_id);
                    $saved_for[$sub_user_id] = $uname["lastname"] . ", " . $uname["firstname"];

                    $member_status = $ass->getMemberStatus($sub_user_id);
                    $member_status->setStatus($values["status"]);
                    $member_status->setNotice($values["notice"]);
                    $member_status->setMark($values["mark"]);
                    $member_status->update();
                }
            }
        }

        if (count($saved_for) > 0) {
            $save_for_str = "(" . implode($saved_for, " - ") . ")";
        }


        if (!empty($user_ids)) {
            global $ilDB;
            $ass = ilExAssignment::getInstancesByExercise($this->exercise->getId());
            $exercise_id = $this->exercise->getId();
            $ass_ids = array();
            foreach ($ass as $assignment) {
                array_push($ass_ids, $assignment->getId());
            }

            foreach ($user_ids as $usr_id) {
                $data = array();
                $query = 'SELECT ilias.exc_mem_ass_status.mark from ilias.exc_mem_ass_status where ilias.exc_mem_ass_status.ass_id in (' . implode(",", $ass_ids) . ') and ilias.exc_mem_ass_status.usr_id = ' . $usr_id . '';
                $res = $ilDB->query($query);
                while ($record = $ilDB->fetchAssoc($res)) {
                    array_push($data, $record);
                }
                $totalmark = 0;
                foreach ($data as $ass_mark) {
                    $totalmark += $ass_mark['mark'];
                }
                $query = 'UPDATE ilias.ut_lp_marks
                    SET  ilias.ut_lp_marks.mark = ' . $totalmark . '
                    WHERE ilias.ut_lp_marks.obj_id = ' . $exercise_id . ' AND ilias.ut_lp_marks.usr_id = ' . $usr_id . '';
                $ilDB->manipulate($query);
            }
        }
        ilUtil::sendSuccess($this->lng->txt("exc_status_saved") . " " . $save_for_str, true);
        $ilCtrl->redirect($this, $this->getViewBack());
    }


    public function selectAssignmentObject()
    {

        global $ilTabs;

        $_GET["grp_id"] = ilUtil::stripSlashes($_POST["grp_id"]);
        $this->group = ilUtil::stripSlashes($_POST["grp_id"]);


        $_GET["ass_id"] = ilUtil::stripSlashes($_POST["ass_id"]);
        $this->selected_assignment = ilUtil::stripSlashes($_POST["ass_id"]);

        $ass = ilExAssignment::getInstancesByExercise($this->exercise->getId());

        foreach ($ass as $as) {
            if ($as->getID() == $this->selected_assignment) {
                $this->assignment = $as;
                $this->assign[0] = $as;
            }
        }
        $this->membersObject();

    }

    protected function checkAccess()
    {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("write", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}

