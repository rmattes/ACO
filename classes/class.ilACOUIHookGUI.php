<?php
include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
/**
 * Class ilACOUIHookGUI
 *
 * This class implements the visability of every tab in our plugin and
 * that they are only visible authorized users, eg. tutor or course admins.
 * 
 * The structur is inspirated by the UIHookGUI class of the courseimport Plugin
 * form studer&raiman (@author Theodor Truffer)
 */
class ilACOUIHookGUI extends ilUIHookPluginGUI
{
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;

	/**
	 * @var ilACOPlugin
	 */
	protected $pl;

	public function __construct()
	{
		global $ilCtrl;
		$this->ctrl = $ilCtrl;
		$this->pl = ilACOPlugin::getInstance();
	}


	function getHTML($a_comp, $a_part, $a_par = array())
	{

	}

	function modifyGUI($a_comp, $a_part, $a_par = array())
	{
        /** @var ilTabsGUI $tabs */
        global $ilTabs;
        
        global $ilAccess, $ilErr;

        $tabs = $a_par['tabs'];

        // Tab im einzelnen Kurs
        if (($_GET["baseClass"] == 'ilRepositoryGUI' || $_GET["baseClass"] == 'ilrepositorygui')
            && $a_part == 'tabs' &&$ilAccess->checkAccess("write", "", $_GET['ref_id'])
            && ilObject::_lookupType($_GET['ref_id'], true) == 'crs'){

            $this->ctrl->setParameterByClass('ilacogroupgui', 'ref_id', $_GET['ref_id']);
            $link1 = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilACOGroupGUI'));
            $tabs->addTab('course_management', $this->pl->txt('tab_course_management'), $link1);

        }

        // Tab in einer Uebung
        if (($_GET["baseClass"] == 'ilExerciseHandlerGUI' || $_GET["baseClass"] == 'ilexercisehandlergui')
            && $a_part == 'tabs'&&$ilAccess->checkAccess("write", "", $_GET['ref_id'])){
            $this->ctrl->setParameterByClass('ilacolinkgui', 'ref_id', $_GET['ref_id']);
            $this->ctrl->setParameterByClass('ilacotutorgui','ref_id',$_GET['ref_id']);
            $link2 = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilACOLinkGUI'));
            $link3 = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilACOTutorGUI'));
            $tabs->addTab('link', $this->pl->txt('tab_link'), $link2);
            $tabs->addTab('tutor',$this->pl->txt('tab_tutor'),$link3);
        }

        //Tab in einem Test
        if (($_GET["baseClass"] == 'ilRepositoryGUI' || $_GET["baseClass"] == 'ilrepositorygui') &&
            $a_part == 'tabs'&&$ilAccess->checkAccess("write", "", $_GET['ref_id'])
            && ilObject::_lookupType($_GET['ref_id'], true) == 'tst'){
            $this->ctrl->setParameterByClass('ilacolinkgui', 'ref_id', $_GET['ref_id']);
            $link4 = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilACOLinkGUI'));
            $tabs->addTab('link', $this->pl->txt('tab_link'), $link4);
        }

	}

}