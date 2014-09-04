<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php';

/**
 * Fhoev import plugin base class
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilFhoevImportPlugin extends ilUserInterfaceHookPlugin
{
	private static $instance = null;

	const CTYPE = 'Services';
	const CNAME = 'UIComponent';
	const SLOT_ID = 'uihk';
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
	 * Run next cron task execution
	 * @param int $a_last_execution_ts
	 * @return bool
	 */
	public function runTask()
	{
		$importer = ilFhoevImport::getInstance();
		$importer->addType(ilFhoevImport::TYPE_CAT);
		$importer->addType(ilFhoevImport::TYPE_USR);
		$importer->addType(ilFhoevImport::TYPE_CRS);
		$importer->addType(ilFhoevImport::TYPE_GRP);

		if(!$importer->checkCronImportRequired())
		{
			ilFhoevLogger::getLogger()->write('Cron interval not exceeded. Aborting');
			return false;
		}
		
		try 
		{
			$importer->import();
			ilFhoevSettings::getInstance()->updateLastCronExecution();
		}
		catch(Exception $e)
		{
			ilFhoevLogger::getLogger()->write("Cron update failed with message: " . $e->getMessage());
		}
		return true;
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
	
}
?>
