<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Cron/classes/class.ilCronJob.php";

/**
 * fhoev import plugin
 * 
 * @author Fabian Wolf <wolf@leifos.com>
 *
 */
class ilFhoevImportCronJob extends ilCronJob
{
	protected $plugin; // [ilCronHookPlugin]
	
	public function getId()
	{
		return ilFhoevImportPlugin::getInstance()->getId();
	}
	
	public function getTitle()
	{		
		return ilFhoevImportPlugin::PNAME;
	}
	
	public function getDescription()
	{
		return ilFhoevImportPlugin::getInstance()->txt("cron_job_info");
	}
	
	public function getDefaultScheduleType()
	{
		return self::SCHEDULE_TYPE_IN_MINUTES;
		return self::SCHEDULE_TYPE_IN_HOURS;
	}
	
	public function getDefaultScheduleValue()
	{
		return ilFhoevSettings::getInstance()->getCronInterval();
	}
	
	public function hasAutoActivation()
	{
		return false;
	}
	
	public function hasFlexibleSchedule()
	{
		return false;
	}
	
	public function hasCustomSettings() 
	{
		return false;
	}
	
	public function run()
	{
		$importer = ilFhoevImport::getInstance();
		$importer->addType(ilFhoevImport::TYPE_CAT);
		$importer->addType(ilFhoevImport::TYPE_USR);
		$importer->addType(ilFhoevImport::TYPE_CRS);
		$importer->addType(ilFhoevImport::TYPE_GRP);

		try
		{
			$importer->import();
			ilFhoevSettings::getInstance()->updateLastCronExecution();
			$status = ilCronJobResult::STATUS_OK;
		}
		catch(Exception $e)
		{
			$status = ilCronJobResult::STATUS_CRASHED;
			ilFhoevLogger::getLogger()->write("Cron update failed with message: " . $e->getMessage());
		}

		$result = new ilCronJobResult();
		$result->setStatus($status);

		return $result;
	}

	public function getPlugin()
	{
		return ilFhoevImportPlugin::getInstance();
	}

}

?>