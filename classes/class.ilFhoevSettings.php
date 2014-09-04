<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilFhoevSettings 
{
	private static $instance = null;
	private $storage = null;
	
	private $lock = false;
	private $import_dir = '';
	private $backup_dir = '';
	private $user = '';
	private $pass = '';
	
	private $cron = false;
	private $cron_interval = 5;
	private $cron_last_execution = 0;

	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->storage = new ilSetting('fhoevimport_config');
		$this->read();
	}
	
	/**
	 * Get singleton instance
	 * 
	 * @return ilFhoevSettings
	 */
	public static function getInstance()
	{
		if(self::$instance)
		{
			return self::$instance;
		}
		return self::$instance = new ilFhoevSettings();
	}
	
	/**
	 * Get storage
	 * @return ilSetting
	 */
	public function getStorage()
	{
		return $this->storage;
	}
	
	public function enableLock($a_lock)
	{
		$this->lock = $a_lock;
	}
	
	public function isLocked()
	{
		return $this->lock;
	}
	
	public function setImportDirectory($a_dir)
	{
		$this->import_dir = $a_dir;
	}
	
	public function getImportDir()
	{
		return $this->import_dir;
	}
	
	public function setBackupDir($a_dir)
	{
		$this->backup_dir = $a_dir;
	}
	
	public function getBackupDir()
	{
		return $this->backup_dir;
	}
	
	public function setSoapUser($a_user)
	{
		$this->user = $a_user;
	}
	
	public function getSoapUser()
	{
		return $this->user;
	}
	
	public function setSoapPass($a_pass)
	{
		$this->pass = $a_pass;
	}
	
	public function getSoapPass()
	{
		return $this->pass;
	}


	public function setCronInterval($a_int)
	{
		$this->cron_interval = $a_int;
	}
	
	public function getCronInterval()
	{
		return $this->cron_interval;
	}
	
	public function getLastCronExecution()
	{
		return $this->cron_last_execution;
	}

	/**
	 * Save settings
	 */
	public function save()
	{
		$this->getStorage()->set('lock',(int) $this->isLocked());
		$this->getStorage()->set('import_dir',$this->getImportDir());
		$this->getStorage()->set('backup_dir',$this->getBackupDir());
		$this->getStorage()->set('soap_user',$this->getSoapUser());
		$this->getStorage()->set('soap_pass',$this->getSoapPass());
		$this->getStorage()->set('cron_interval',$this->getCronInterval());
	}
	
	public function updateLastCronExecution()
	{
		$this->getStorage()->set('cron_last_execution',time());
	}
	
	/**
	 * Create directories
	 * 
	 * @throws ilFhoevIOException
	 */
	public function createDirectories()
	{
		if(!ilUtil::makeDirParents($this->getImportDir()))
		{
			throw new ilFhoevIOException("Cannot create import directory.");
		}
		if(!ilUtil::makeDirParents($this->getBackupDir()))
		{
			throw new ilFhoevIOException("Cannot create backup directory.");
		}
	}
	
	/**
	 * Read (default) settings
	 */
	protected function read()
	{
		$this->setImportDirectory(ilUtil::getDataDir().'/fhoevImport');
		$this->setBackupDir(ilUtil::getDataDir().'/fhoevImported');

		$this->setImportDirectory($this->getStorage()->get('import_dir', $this->getImportDir()));
		$this->setBackupDir($this->getStorage()->get('backup_dir',$this->getBackupDir()));
		$this->enableLock($this->getStorage()->get('lock',$this->isLocked()));
		$this->setSoapUser($this->getStorage()->get('soap_user', $this->getSoapUser()));
		$this->setSoapPass($this->getStorage()->get('soap_pass', $this->getSoapPass()));
		$this->setCronInterval($this->getStorage()->get('cron_interval',$this->getCronInterval()));
		$this->cron_last_execution = $this->getStorage()->get('cron_last_execution',0);
	}
	
}
?>
