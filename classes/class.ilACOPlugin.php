<?php
include_once("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");

/**
 * Class ilACOPlugin
 *
 * @author  Manuel Mergl
 */
class ilACOPlugin extends ilUserInterfaceHookPlugin
{
    /**
     * @var ilACOPlugin
     */
    protected static $instance;

    /**
     * @return ilACOPlugin
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function getPluginName()
    {
        return "ACO";
    }

}