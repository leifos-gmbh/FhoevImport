<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilFhoevImport 
{
	protected static $instance = null;
	protected static $import_file_pattern = array(
		'cat'	=> 'il_categories',
		'usr'	=> 'il_users',
		'crs'	=> 'il_courses',
		'grp'	=> 'il_groups'
	);
	
	protected $working_dir = '';
	protected $error_dir = '';
	
	const IMPORT_ALL = 1;
	const IMPORT_SELECTED = 2;
	
	const TYPE_CAT = 'cat';
	const TYPE_USR = 'usr';
	const TYPE_CRS = 'crs';
	const TYPE_GRP = 'grp';

	
	private $types = array();
	
	/**
	 * Get import instance
	 * 
	 * @return ilFhoevImport
	 */
	public static function getInstance()
	{
		if(self::$instance)
		{
			return self::$instance;
		}
		return self::$instance = new self();
	}
	
	public function getWorkingDirectory()
	{
		return $this->working_dir;
	}
	
	public function getErrorDirectory()
	{
		return $this->error_dir;
	}
	

	/**
	 * Add import type
	 * @param type $a_type
	 */
	public function addType($a_type)
	{
		$this->types[] = $a_type;
	}

	/**
	 * Do import
	 * @throws ilException
	 */
	public function import()
	{
		ilFhoevLogger::getLogger()->write('Starting import...');
		
		// Checking for import lock
		if(ilFhoevSettings::getInstance()->isLocked())
		{
			throw new ilFhoevIOException(ilFhoevImportPlugin::getInstance()->txt('err_import_locked'));
		}
		
		$this->setLock();
		$this->initImportDirectory();
		
		try {
			foreach($this->types as $type)
			{
				ilFhoevLogger::getLogger()->write('Using type: ' . $type);
				switch($type)
				{
					case self::TYPE_CAT:
						$this->importFiles('cat');
						break;

					case self::TYPE_USR:
						$this->importFiles('usr');
						break;

					case self::TYPE_CRS:
						$this->importFiles('crs');
						break;

					case self::TYPE_GRP:
						$this->importFiles('grp');
						break;
				}
			}
			$this->releaseLock();
		}
		catch(ilFhoevIOException $e)
		{
			$this->releaseLock();
			throw $e;
		}
	}
	
	
	/**
	 * Import files
	 * @param type $a_type 
	 */
	protected function importFiles($a_type)
	{
		foreach($this->lookupImportFilesByType($a_type, $this->working_dir) as $file)
		{
			ilFhoevLogger::getLogger()->write("Handling new file " . $file);
			switch($a_type)
			{
				case 'cat':
					$this->importCategories($file);
					break;
				case 'usr':
					$this->importUsers($file);
					break;
				case 'crs':
					$this->importCourses($file);
					break;
				case 'grp':
					$this->importGroups($file);
					break;
			}
		}
	}
	
	/**
	 * Release lock
	 */
	protected function releaseLock()
	{
		// Settings import lock
		ilFhoevLogger::getLogger()->write('Release import lock');
		ilFhoevSettings::getInstance()->enableLock(false);
		ilFhoevSettings::getInstance()->save();
		
		// Delete working dir
		ilFhoevLogger::getLogger()->write('Deleting working directory');
		ilUtil::delDir($this->getWorkingDirectory());
	}
	
	/**
	 * Set import lock
	 */
	protected function setLock()
	{
		// Settings import lock
		ilFhoevLogger::getLogger()->write('Setting import lock');
		ilFhoevSettings::getInstance()->enableLock(true);
		ilFhoevSettings::getInstance()->save();
	}
	
	/**
	 * Import Groups
	 */
	protected function importGroups($file)
	{
		ilFhoevLogger::getLogger()->write(
				'Using import file: '. $this->getWorkingDirectory().'/'.$file
		);
		if(!$this->checkFileAvailable($file))
		{
			ilFhoevLogger::getLogger()->write('No import file found for groups.');
			return false;
		}
		try {
			$parser = new ilFhoevGroupImportParser($this->getWorkingDirectory().'/'.$file);
			$parser->startParsing();
		}
		catch(Exception $e) {
			$this->rename($file,$parser->hasErrors());
			throw $e;
		}
		
		
		$this->rename($file);
		
	}
	
	/**
	 * Import categories
	 * @thows ilException
	 */
	protected function importCategories($file)
	{
		ilFhoevLogger::getLogger()->write(
				'Using import file: '. $this->getWorkingDirectory().'/'.$file
		);
		if(!$this->checkFileAvailable($file))
		{
			ilFhoevLogger::getLogger()->write('No import file found for categories.');
			return false;
		}
		try {
			$parser = new ilFhoevCategoryImportParser($this->getWorkingDirectory().'/'.$file);
			$parser->startParsing();
		}
		catch(Exception $e) {
			$this->rename($file,true);
			throw $e;
		}
		
		
		$this->rename($file);
	}
	
	/**
	 * import users
	 * @return type 
	 */
	protected function importUsers($file)
	{
		ilFhoevLogger::getLogger()->write(
				'Using import file: '. $this->getWorkingDirectory().'/'.$file
		);
		if(!$this->checkFileAvailable($file))
		{
			ilFhoevLogger::getLogger()->write('No import file found for users.');
			return false;
		}		
		try {
			$parser = new ilFhoevUserImportParser($this->getWorkingDirectory().'/'.$file);
			$parser->startParsing();
		}
		catch(Exception $e) {
			$this->rename($file,true);
			throw $e;
		}
		$this->rename($file);
	}
	
	/**
	 * import courses
	 * @return type 
	 */
	protected function importCourses($file)
	{
		ilFhoevLogger::getLogger()->write(
				'Using import file: '. $this->getWorkingDirectory().'/'.$file
		);
		if(!$this->checkFileAvailable($file))
		{
			ilFhoevLogger::getLogger()->write('No import file found for courses.');
			return false;
		}
		try {
			$parser = new ilFhoevCourseImportParser($this->getWorkingDirectory().'/'.$file);
			$parser->startParsing();
		}
		catch(Exception $e) {
			$this->rename($file,true);
			throw $e;
		}

		$this->rename($file, $parser->hasErrors());
	}
	
	
	/**
	 * Check if file exists
	 * @return bool
	 */
	protected function checkFileAvailable($file)
	{
		if(!file_exists($this->getWorkingDirectory().'/'.$file))
		{
			return false;
		}
		if(!is_readable($this->getWorkingDirectory().'/'.$file))
		{
			ilFhoevLogger::getLogger('No permission to read file content: '.$this->getWorkingDirectory().'/'.$file);
			throw new ilFhoevIOException('No permission to read file content: '.$this->getWorkingDirectory().'/'.$file);
		}
		return true;
	}
	
	/**
	 * Rename file and store in backup
	 * @param type $file 
	 */
	protected function rename($file, $a_error_file = false)
	{
		$now = new ilDateTime(time(),IL_CAL_UNIX);
		$now_string = $now->get(IL_CAL_FKT_DATE,'_Y-m-d_H:i_');
		
		if(!$a_error_file)
		{
			rename(
					$this->getWorkingDirectory().'/'.$file,
					ilFhoevSettings::getInstance()->getBackupDir().'/'.$now_string.$file
				);
		}
		else
		{
			$this->initErrorDirectory();
			rename(
					$this->getWorkingDirectory().'/'.$file,
					$this->getErrorDirectory().'/'.$file
				);
					
		}
	}
	
	/**
	 * Lookup all import files of a specific type
	 * @param string $a_type
	 * @return array files 
	 */
	protected function lookupImportFilesByType($a_type,$a_dir)
	{
		$validFiles = array();

		ilFhoevLogger::getLogger()->write('Scanning directory: ' . $a_dir);
		
		$ite = new DirectoryIterator(($a_dir));
		foreach($ite as $fileInfo)
		{
			ilFhoevLogger::getLogger()->write('Handling file '. $fileInfo->getFilename());
			if($fileInfo->isDot())
			{
				continue;
			}
			$name = $fileInfo->getFilename();
			if(strpos($name, self::$import_file_pattern[$a_type]) !== 0)
			{
				ilFhoevLogger::getLogger()->write('Ignoring file '.$name.' for type '.$a_type);
				continue;
			}
			$validFiles[] = $name;
		}
		
		sort($validFiles);
		return $validFiles;
	}
	
	/**
	 * Init import directory
	 * @return boolean
	 */
	protected function initImportDirectory()
	{
		$dirname = dirname(ilFhoevSettings::getInstance()->getImportDir());
		$this->working_dir = $dirname.'/importWorking_'.date('Y-m-d_H_i');

		// Create new working directory
		ilUtil::makeDirParents($this->working_dir);
		
		// Copy valid import files
		foreach(array(self::TYPE_CAT,self::TYPE_CRS,self::TYPE_GRP,self::TYPE_USR) as $type)
		{
			$valid_files = $this->lookupImportFilesByType($type,  ilFhoevSettings::getInstance()->getImportDir());
			foreach($valid_files as $file)
			{
				@rename(ilFhoevSettings::getInstance()->getImportDir().'/'.$file, $this->working_dir.'/'.$file);
			}
		}
		return true;
	}
	
	protected function initErrorDirectory()
	{
		$dirname = dirname(ilFhoevSettings::getInstance()->getImportDir());
		$this->error_dir = $dirname.'/importError_'.date('Y-m-d_H:i');

		// Create new working directory
		ilUtil::makeDirParents($this->error_dir);
		
	}
}
?>
