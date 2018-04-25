<?php
require_once './Services/Form/classes/class.ilTextInputGUI.php';
require_once './Services/Form/classes/class.ilPropertyFormGUI.php';

define('IL_GRP_MEMBER', 5);

/**
 * Created by PhpStorm.
 * User: Manuel
 * Date: 19.01.2017
 * Time: 12:40
 * @ilCtrl_IsCalledBy ilACOMemberGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilACOMemberGUI: ilObjCourseAdministrationGUI
 * @ilCtrl_Calls      ilACOMemberGUI: ilRepositorySearchGUI
 * @ilCtrl_Calls      ilACOMemberGUI: ilObjCourseGUI
 *
 * This class implements the functionality of the tab move members.
 * Which includes the the move of the member itself but also the drop down menu,
 * the auto completion and error messages for wrong input.
 *
 *

 */
class ilACOMemberGUI
{
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
    protected $course_id;

    protected $userLogin;
    protected $groupTitle;
    protected $destinationTitle;

    protected $group_title;
    protected $destination_title;

    public function __construct()
    {
        global $tree, $ilCtrl, $tpl, $ilTabs, $ilLocator, $lng;
        $this->tree = $tree;
        $this->lng = $lng;
        $this->tabs = $ilTabs;
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->course_id = $_GET['ref_id'];
        $this->ilLocator = $ilLocator;
        $this->pl = ilACOPlugin::getInstance();
    }

    protected function prepareOutput()
    {

        global $ilLocator, $tpl;

        $this->ctrl->setParameterByClass('ilobjcourseadministrationgui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilacogroupgui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilacogroupdisplaygui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilacomembergui', 'ref_id', $_GET['ref_id']);
        $this->ctrl->setParameterByClass('ilrepositorygui', 'ref_id', $_GET['ref_id']);

        $this->tabs->addTab('course_management', $this->pl->txt('tab_course_management'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilACOGroupGUI')));

        $this->tabs->addSubTab('group_create', $this->pl->txt('group_create'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilACOGroupGUI')));
        $this->tabs->addSubTab('course_edit', $this->pl->txt('course_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilACOGroupDisplayGUI')));
        $this->tabs->addSubTab('member_edit', $this->pl->txt('member_edit'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilACOMemberGUI')));
        $this->tabs->activateSubTab('member_edit');

        $this->ctrl->getRedirectSource();

        $this->tabs->setBackTarget($this->pl->txt('back'), $this->ctrl->getLinkTargetByClass(array(
            'ilrepositorygui',
            'ilrepositorygui',
        )));

        $this->setTitleAndIcon();

        $ilLocator->addRepositoryItems($_GET['ref_id']);
        $tpl->setLocator();
    }

    protected function setTitleAndIcon()
    {
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_crs.svg'));
        $this->tpl->setTitle($this->pl->txt('obj_acop'));
        $this->tpl->setDescription($this->pl->txt('obj_acop_desc'));
    }

    /**
     *
     */
    public function executeCommand()
    {
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
    protected function view()
    {

        $form = $this->initForm();
        $form->setValuesByPost();
        $this->tpl->setContent($form->getHTML());
    }

    protected function initForm()
    {
        global $lng, $ilCtrl;
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->pl->txt('member_edit'));
        $form->setDescription($this->pl->txt('member_description'));
        $form->setId('member_edit');
        $form->setFormAction($this->ctrl->getFormAction($this));

        $a_options = array(
            'auto_complete_name' => $lng->txt('user'),
        );

        $ajax_url = $ilCtrl->getLinkTargetByClass(array(get_class($this), 'ilRepositorySearchGUI'),
            'doUserAutoComplete', '', true, false);

        $this->userLogin = new ilTextInputGUI($a_options['auto_complete_name'], 'user_login');
        $this->userLogin->setDataSource($ajax_url);
        $this->userLogin->setRequired(true);

        $form->addItem($this->userLogin);
        $form->addCommandButton(selectMember, $this->pl->txt(select_member));

        return $form;
    }

    protected function selectMember()
    {
        global $lng, $ilCtrl;
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->pl->txt('member_edit'));
        $form->setDescription($this->pl->txt('member_description'));
        $form->setId('member_edit');
        $form->setFormAction($this->ctrl->getFormAction($this));

        $this->userLogin = new ilTextInputGUI($this->pl->txt('member_login'), 'user_login');
        $this->userLogin->setValue($_POST["user_login"]);
        $this->userLogin->setDisabled(true);

        $this->groupTitle = new ilSelectInputGUI($this->pl->txt('group_title'), 'group_title');
        $this->groupTitle->setOptions($this->getGroupsWhereMember($this->getMemberIdByLogin($_POST["user_login"])));

        $this->destinationTitle = new ilSelectInputGUI($this->pl->txt('destination_title'), 'destination_title');
        $this->destinationTitle->setOptions($this->getGroupsWhereNotMember($this->getMemberIdByLogin($_POST["user_login"])));

        $this->userLogin->setRequired(true);
        $this->groupTitle->setRequired(true);
        $this->destinationTitle->setRequired(true);

        $form->addItem($this->userLogin);
        $form->addItem($this->groupTitle);
        $form->addItem($this->destinationTitle);
        $form->addCommandButton('moveMember', $this->pl->txt('move_member'));

        $form->setValuesByPost();
        $this->tpl->setContent($form->getHTML());

        return $form;
    }

    /**
     * Do auto completion
     * @return void
     */
    protected function doUserAutoComplete()
    {
        $a_fields = array('login', 'firstname', 'lastname', 'email');
        $result_field = 'login';

        include_once './Services/User/classes/class.ilUserAutoComplete.php';
        $auto = new ilUserAutoComplete();

        if (($_REQUEST['fetchall'])) {
            $auto->setLimit(ilUserAutoComplete::MAX_ENTRIES);
        }

        $auto->setSearchFields($a_fields);
        $auto->setResultField($result_field);
        $auto->enableFieldSearchableCheck(true);

        echo $auto->getList($_REQUEST['term']);
        exit();
    }

    protected function moveMember()
    {
        $form = $this->selectMember();
        $form->setValuesByPost();

        $member_login = $this->userLogin->getValue();
        $optionsGroupTitle = $this->groupTitle->getOptions();
        $optionsDestinationTitle = $this->destinationTitle->getOptions();

        $this->group_title = $optionsGroupTitle[$this->groupTitle->getValue()];
        $this->destination_title = $optionsDestinationTitle[$this->destinationTitle->getValue()];

        $member_id = $this->getMemberIdByLogin($member_login);
        $group_id = $this->getGroupIdByTitle($this->group_title, $this->course_id);
        $destination_id = $this->getGroupIdByTitle($this->destination_title, $this->course_id);

        $description_dest = "Groupmember of group obj_no." . $destination_id[0]["obj_id"];
        $description_source = "Groupmember of group obj_no." . $group_id[0]["obj_id"];

        $role_id_dest = $this->getRoleID($description_dest);
        $role_id_source = $this->getRoleID($description_source);

        $group_id = $group_id[0]["obj_id"];
        $destination_id = $destination_id[0]["obj_id"];
        $role_id_source = $role_id_source[0]["obj_id"];
        $role_id_dest = $role_id_dest[0]["obj_id"];

        if (($this->checkIfGroupExists($group_id)) and ($this->checkIfGroupExists($destination_id)) and
            ($this->checkIfUserExistsInGroup($member_id, $group_id)) and
            ($this->checkIfUserNotExistsInGroup($member_id, $destination_id))
        ) {

            $this->manipulateDB($member_id, $role_id_source, $destination_id, $role_id_dest, $group_id);

        }

        $this->view();

    }

    protected function getRoleID($description)
    {
        global $ilDB;
        $role_id = array();
        $query = "SELECT od.obj_id FROM object_data as od WHERE od.description = '" . $description . "'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)) {
            array_push($role_id, $record);
        }

        return $role_id;
    }

    protected function manipulateDB($member_id, $role_id_source, $destination_id, $role_id_dest, $source_id)
    {
        global $ilDB;

        //insert in RBAC
        $query = "INSERT INTO rbac_ua (usr_id, rol_id) " .
            "VALUES (" . $member_id . "," . $role_id_dest . ")";
        $res = $ilDB->manipulate($query);

        //delete OLD from RBAC
        $query = "DELETE FROM rbac_ua
            WHERE usr_id = " . $member_id . "
            AND rol_id = " . $role_id_source . " ";
        $res = $ilDB->manipulate($query);

        $query = "UPDATE obj_members as om
        SET om.obj_id = '" . $destination_id . "' WHERE om.usr_id = '" . $member_id . "' AND om.obj_id = '"
            . $source_id . "' AND om.member = 1";
        $ilDB->manipulate($query);


        ilUtil::sendSuccess($this->userLogin->getValue() . $this->pl->txt("movedSuccessful") .
            $this->group_title . $this->pl->txt("movedTo") . $this->destination_title . $this->pl->txt("moved"));

    }

    protected function getGroupIdByTitle($group_title, $course_id)
    {
        global $ilDB;
        $group_id = array();

        $query = "select oref.obj_id from crs_items as citem
                  join object_reference as oref on oref.ref_id = citem.obj_id
                  join object_data as od on oref.obj_id = od.obj_id
                  join crs_items as ci on oref.ref_id = ci.obj_id
                  where od.title='" . $group_title . "' and ci.parent_id='" . $_GET['ref_id'] . "' and oref.deleted is null";
        $result = $ilDB->queryF($query, array('text', 'integer'), array($group_title, $course_id));
        while ($record = $ilDB->fetchAssoc($result)) {
            array_push($group_id, $record);
        }

        return $group_id;
    }

    protected function getMemberIdByLogin($member_login)
    {
        global $ilDB;

        $member_id = array();
        $query = "select usr_id from usr_data as ud where (ud.login='" . $member_login . "')";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)) {
            array_push($member_id, $record);
        }

        return $member_id[0]["usr_id"];

    }

    //Uberpruefung, ob User in der bisherigen Gruppe vorhanden
    protected function checkIfUserExistsInGroup($member_id, $group_id)
    {
        global $ilDB;

        $queryResult = array();

        $query = "SELECT COUNT(*) FROM object_data as od
                  join object_reference as obr on obr.obj_id = od.obj_id
                  join obj_members as om on obr.obj_id = om.obj_id
                  WHERE obr.deleted is null and od.obj_id = '" . $group_id . "' and om.usr_id = '" . $member_id . "'";

        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)) {
            array_push($queryResult, $record);
        }


        if (strcmp($queryResult[0]["COUNT(*)"], "1") !== 0) {
            ilUtil::sendFailure($this->pl->txt("userInGroupNotExistent") . ' ' . $this->pl->txt("only_in") . ' ' . implode(' - ', $this->getGroupsWhereMember($member_id["usr_id"])), true);
            return false;
        } else {
            return true;
        }
    }

    //Uberpruefung, ob User in der neuen Gruppe schon vorhanden
    protected function checkIfUserNotExistsInGroup($member_id, $group_id)
    {
        global $ilDB;

        $queryResult = array();

        $query = "SELECT COUNT(*) FROM object_data as od
                  join object_reference as obr on obr.obj_id = od.obj_id
                  join obj_members as om on obr.obj_id = om.obj_id
                  WHERE obr.deleted is null and od.obj_id = '" . $group_id . "' and om.usr_id = '" . $member_id . "'";

        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)) {
            array_push($queryResult, $record);
        }

        if (strcmp($queryResult[0]["COUNT(*)"], "1") == 0) {
            ilUtil::sendFailure($this->pl->txt("userInGroupExistent"), true);
            return false;
        } else {
            return true;
        }

    }


    // Ueberpruefung, ob die Gruppe exisitiert
    protected function checkIfGroupExists($group_id)
    {
        global $ilDB;

        $queryResult = array();

        $query = "SELECT COUNT(*) FROM object_data as od
                  join object_reference as obr on obr.obj_id = od.obj_id
                  WHERE obr.deleted is null and od.obj_id = '" . $group_id . "'";

        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)) {
            array_push($queryResult, $record);
        }

        if (strcmp($queryResult[0]["COUNT(*)"], "1") !== 0) {
            ilUtil::sendFailure($this->pl->txt("groupNotExistent"), true);
            return false;
        } else {
            return true;
        }
    }

    protected function getGroups()
    {

        global $ilDB;

        $data = array();
        $query = "select od.title as 'title'
                    from object_data as od
                    join object_reference as oref on oref.obj_id = od.obj_id
                    join tree tree on tree.child = oref.ref_id
                    where oref.deleted is null and od.`type`='grp' and tree.parent = '" . $_GET['ref_id'] . "'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)) {
            array_push($data, $record);
        }

        $output = array();

        foreach ($data as $result) {
            array_push($output, $result['title']);
        }

        return $output;
    }

    protected function getGroupsWhereMember($usr_id)
    {

        global $ilDB;

        $data = array();
        $query = "select od.title as 'title'
                    from object_data as od
                    join object_reference as oref on oref.obj_id = od.obj_id
                    join crs_items citem on citem.obj_id = oref.ref_id
                    join obj_members as om on om.obj_id = oref.obj_id
                    join usr_data as ud on ud.usr_id = om.usr_id
                    where oref.deleted is null and od.`type`='grp' and
                      citem.parent_id = '" . $_GET['ref_id'] . "' and om.usr_id='" . $usr_id . "'";
        $result = $ilDB->query($query);
        while ($record = $ilDB->fetchAssoc($result)) {
            array_push($data, $record);
        }

        $output = array();

        foreach ($data as $result) {
            array_push($output, $result['title']);
        }

        return $output;

    }

    protected function getGroupsWhereNotMember($usr_id)
    {

        global $ilDB;

        $data1 = array();
        $query1 = "select od.obj_id as 'obj_id'
                    from object_data as od
                    join object_reference as oref on oref.obj_id = od.obj_id
                    join crs_items citem on citem.obj_id = oref.ref_id
                    join obj_members as om on om.obj_id = oref.obj_id
                    join usr_data as ud on ud.usr_id = om.usr_id
                    where oref.deleted is null and od.`type`='grp' and
                      citem.parent_id = '" . $_GET['ref_id'] . "' and om.usr_id='" . $usr_id . "'";
        $result1 = $ilDB->query($query1);
        while ($record1 = $ilDB->fetchAssoc($result1)) {
            array_push($data1, $record1);
        }

        $groupsIDWhereMemberTMP = array();

        foreach ($data1 as $result1) {
            array_push($groupsIDWhereMemberTMP, $result1['obj_id']);
        }

        $groupsIDWhereMember = "'" . implode("','", $groupsIDWhereMemberTMP) . "'";

        $data = array();

        $query = "select distinct od.title as 'title'
                    from object_data as od
                    join object_reference as oref on oref.obj_id = od.obj_id
                    join crs_items citem on citem.obj_id = oref.ref_id
                    join obj_members as om on om.obj_id = oref.obj_id
                    join usr_data as ud on ud.usr_id = om.usr_id
                    where oref.deleted is null and od.`type`='grp' and
                      citem.parent_id = '" . $_GET['ref_id'] . "' and om.obj_id not in (" . $groupsIDWhereMember . ")";
        $result = $ilDB->query($query);

        while ($record = $ilDB->fetchAssoc($result)) {
            array_push($data, $record);
        }

        $output = array();

        foreach ($data as $result) {
            array_push($output, $result['title']);
        }

        return $output;

    }

    protected function checkAccess()
    {
        global $ilAccess, $ilErr;
        if (!$ilAccess->checkAccess("read", "", $_GET['ref_id'])) {
            $ilErr->raiseError($this->lng->txt("no_permission"), $ilErr->WARNING);
        }
    }
}
