<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Cron/classes/class.ilCronHookPlugin.php");

/**
 * Fhoev import plugin base class
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilFhoevImportPlugin extends ilCronHookPlugin
{
	private static $instance = null;

	const CTYPE = 'Services';
	const CNAME = 'Cron';
	const SLOT_ID = 'crnhk';
	const PNAME = 'FhoevImport';

	/**
	 * Get singelton instance
	 * @global ilPluginAdmin $ilPluginAdmin
	 * @return ilFhoevImportPlugin
	 */
	public static function getInstance()
	{
		global $ilPluginAdmin;

		if(self::$instance)
		{
			return self::$instance;
		}
		include_once './Services/Component/classes/class.ilPluginAdmin.php';
		return self::$instance = ilPluginAdmin::getPluginObject(
			self::CTYPE,
			self::CNAME,
			self::SLOT_ID,
			self::PNAME
		);
	}
	
	/**
	 * Get plugin name
	 * @return string
	 */
	public function getPluginName()
	{
		return self::PNAME;
	}
	
	/**
	 * Init auto load
	 */
	protected function init()
	{
		$this->initAutoLoad();
	}
		
	/**
	 * Init auto loader
	 * @return void
	 */
	protected function initAutoLoad()
	{
		spl_autoload_register(
			array($this,'autoLoad')
		);
	}

	/**
	 * Auto load implementation
	 *
	 * @param string class name
	 */
	private final function autoLoad($a_classname)
	{
		$class_file = $this->getClassesDirectory().'/class.'.$a_classname.'.php';
		if(@include_once($class_file))
		{
			return;
		}
	}

	public function getCronJobInstance($a_id)
	{
		global $ilLog;
		$job = new ilFhoevImportCronJob();
		$ilLog->write("getinstance new job-> ".$job->getId());
		return $job;
	}
	public function getCronJobInstances()
	{
		global $ilLog;
		$job = new ilFhoevImportCronJob();
		$ilLog->write("getinstances new job-> ".$job->getId());
		return array($job);
	}
	
}
?>
