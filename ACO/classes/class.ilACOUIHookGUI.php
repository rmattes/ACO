<?php
include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
/**
 * Class ilACOUIHookGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
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