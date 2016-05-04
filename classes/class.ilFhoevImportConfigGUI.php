<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Component/classes/class.ilPluginConfigGUI.php';

/**
 * Description of class
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilFhoevImportConfigGUI extends ilPluginConfigGUI
{
	/**
	* Handles all commmands, default is "configure"
	*/
	public function performCommand($cmd)
	{
		global $ilCtrl;
		global $ilTabs;
		

		$ilTabs->addTab(
			'settings',
			ilFhoevImportPlugin::getInstance()->txt('tab_settings'),
			$GLOBALS['ilCtrl']->getLinkTarget($this,'configure')
		);
		
		$ilTabs->addTab(
			'import',
			ilFhoevImportPlugin::getInstance()->txt('tab_import'),
			$GLOBALS['ilCtrl']->getLinkTarget($this,'import')
		);
		
		$ilTabs->addTab(
			'migration',
			ilFhoevImportPlugin::getInstance()->txt('tab_migration'),
			$GLOBALS['ilCtrl']->getLinkTarget($this, 'initMigration')
		);
		
		
		$ilCtrl->saveParameter($this, "menu_id");
		
		switch ($cmd)
		{
			default:
				$this->$cmd();
				break;

		}
	}

	/**
	 * Show settings screen
	 * @global type $tpl
	 * @global type $ilTabs 
	 */
	protected function configure(ilPropertyFormGUI $form = null)
	{
		global $tpl, $ilTabs;

		$ilTabs->activateTab('settings');

		if(!$form instanceof ilPropertyFormGUI)
		{
			$form = $this->initConfigurationForm();
		}
		$tpl->setContent($form->getHTML());
	}
	
	/**
	 * Init configuration form
	 * @global type $ilCtrl 
	 */
	protected function initConfigurationForm()
	{
		global $ilCtrl, $lng;
		
		$settings = ilFhoevSettings::getInstance();
		
		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->getPluginObject()->txt('tbl_fhoev_settings'));
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->addCommandButton('save', $lng->txt('save'));
		$form->setShowTopButtons(false);
		
		$lock = new ilCheckboxInputGUI($this->getPluginObject()->txt('tbl_settting_lock'),'lock');
		$lock->setValue(1);
		$lock->setDisabled(!$settings->isLocked());
		$lock->setChecked($settings->isLocked());
		$form->addItem($lock);
		
		$soap_user = new ilTextInputGUI($this->getPluginObject()->txt('tbl_setting_soap_user'),'user');
		$soap_user->setValue($settings->getSoapUser());
		$soap_user->setRequired(true);
		$soap_user->setSize(16);
		$soap_user->setMaxLength(128);
		$form->addItem($soap_user);
		
		$soap_pass = new ilPasswordInputGUI($this->getPluginObject()->txt('tbl_setting_soap_pass'),'pass');
		$soap_pass->setValue($settings->getSoapPass());
		$soap_pass->setSkipSyntaxCheck(TRUE);
		$soap_pass->setRetype(false);
		$soap_pass->setRequired(true);
		$soap_pass->setSize(16);
		$soap_pass->setMaxLength(128);
		$form->addItem($soap_pass);
		
		$import = new ilTextInputGUI($this->getPluginObject()->txt('tbl_settings_import'),'import');
		$import->setRequired(true);
		$import->setSize(120);
		$import->setMaxLength(512);
		$import->setValue($settings->getImportDir());
		$form->addItem($import);
		
		$backup = new ilTextInputGUI($this->getPluginObject()->txt('tbl_settings_backup'),'backup');
		$backup->setRequired(true);
		$backup->setSize(120);
		$backup->setMaxLength(512);
		$backup->setValue($settings->getBackupDir());
		$form->addItem($backup);
		
		// cron intervall
		$cron_i = new ilNumberInputGUI($this->getPluginObject()->txt('cron'),'cron_interval');
		$cron_i->setMinValue(1);
		$cron_i->setSize(2);
		$cron_i->setMaxLength(3);
		$cron_i->setRequired(true);
		$cron_i->setValue($settings->getCronInterval());
		$cron_i->setInfo($this->getPluginObject()->txt('cron_interval'));
		$form->addItem($cron_i);
		
		return $form;
	}
	
	/**
	 * Save settings
	 */
	protected function save()
	{
		global $lng, $ilCtrl;
		
		$form = $this->initConfigurationForm();
		$settings = ilFhoevSettings::getInstance();
		
		try {
		
			if($form->checkInput())
			{
				$settings->enableLock($form->getInput('lock'));
				$settings->setImportDirectory($form->getInput('import'));
				$settings->setBackupDir($form->getInput('backup'));
				$settings->setSoapUser($form->getInput('user'));
				$settings->setSoapPass($form->getInput('pass'));
				$settings->setCronInterval($form->getInput('cron_interval'));
				$settings->save();
				
				$settings->createDirectories();

				ilUtil::sendSuccess($lng->txt('settings_saved'),true);
				$ilCtrl->redirect($this,'configure');
			}
			$error = $lng->txt('err_check_input');
		}
		catch(ilFhoevIOException $e) 
		{
			$error = $e->getMessage();
		}
		$form->setValuesByPost();
		ilUtil::sendFailure($e);
		$this->configure($form);
	}
	
	/**
	 * Start import
	 */
	protected function import(ilPropertyFormGUI $form = null)
	{
		global $tpl, $ilTabs;

		$ilTabs->activateTab('import');

		if(!$form instanceof ilPropertyFormGUI)
		{
			$form = $this->initImportForm();
		}
		$tpl->setContent($form->getHTML());
	}
	
	protected function initImportForm()
	{
		global $ilCtrl, $lng;
		
		$settings = ilFhoevSettings::getInstance();
		
		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->getPluginObject()->txt('tbl_fhoev_import'));
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->addCommandButton('doImport', $this->getPluginObject()->txt('btn_import'));
		$form->setShowTopButtons(false);
		
		// selection all or single elements
		$imp_type = new ilRadioGroupInputGUI($this->getPluginObject()->txt('import_selection'),'selection');
		$imp_type->setRequired(true);
		$imp_type->setValue(ilFhoevImport::IMPORT_ALL);
		$form->addItem($imp_type);
		
		$all = new ilRadioOption($this->getPluginObject()->txt('import_selection_all'), ilFhoevImport::IMPORT_ALL);
		$imp_type->addOption($all);
		
		$sel = new ilRadioOption($this->getPluginObject()->txt('import_selection_selected'), ilFhoevImport::IMPORT_SELECTED);
		$imp_type->addOption($sel);
		
		// Selection of types
		$cats = new ilCheckboxInputGUI($lng->txt('objs_cat'),'cat');
		$cats->setValue(ilFhoevImport::TYPE_CAT);
		$sel->addSubItem($cats);
		
		$usr = new ilCheckboxInputGUI($lng->txt('objs_usr'),'usr');
		$usr->setValue(ilFhoevImport::TYPE_USR);
		$sel->addSubItem($usr);

		$crs = new ilCheckboxInputGUI($lng->txt('objs_crs'),'crs');
		$crs->setValue(ilFhoevImport::TYPE_CRS);
		$sel->addSubItem($crs);

		$grp = new ilCheckboxInputGUI($lng->txt('objs_grp'),'grp');
		$grp->setValue(ilFhoevImport::TYPE_GRP);
		$sel->addSubItem($grp);
		
		return $form;
	}
	
	/**
	 * Do import
	 */
	protected function doImport()
	{
		global $lng, $ilCtrl;
		
		$form = $this->initImportForm();
		$import = ilFhoevImport::getInstance();
		
		if($form->checkInput())
		{
			if($form->getInput('selection') == ilFhoevImport::IMPORT_ALL)
			{
				$import->addType(ilFhoevImport::TYPE_CAT);
				$import->addType(ilFhoevImport::TYPE_USR);
				$import->addType(ilFhoevImport::TYPE_CRS);
				$import->addType(ilFhoevImport::TYPE_GRP);
			}
			else
			{
				if($form->getInput('cat'))
				{
					$import->addType(ilFhoevImport::TYPE_CAT);
				}
				if($form->getInput('usr'))
				{
					$import->addType(ilFhoevImport::TYPE_USR);
				}
				if($form->getInput('crs'))
				{
					$import->addType(ilFhoevImport::TYPE_CRS);
				}
				if($form->getInput('grp'))
				{
					$import->addType(ilFhoevImport::TYPE_GRP);
				}
			}
			
			// Perform import
			try 
			{
				$import->import();
				ilUtil::sendSuccess($this->getPluginObject()->txt('import_success'),true);
				$ilCtrl->redirect($this,'import');
			}
			catch(ilException $e)
			{
				ilUtil::sendFailure($e->getMessage(),true);
				$ilCtrl->redirect($this,'import');
			}
		}
		ilUtil::sendFailure($lng->txt('err_check_input'));
		$this->import($form);
	}
	
	/**
	 * Init main course migration
	 * @param \ilPropertyFormGUI $form
	 */
	protected function initMigration(ilPropertyFormGUI $form = null)
	{
		global $tpl, $ilTabs;

		$ilTabs->activateTab('migration');

		if(!$form instanceof ilPropertyFormGUI)
		{
			$form = $this->initMigrationForm();
		}
		$tpl->setContent($form->getHTML());

	}
	
	/**
	 * init migration form
	 */
	protected function initMigrationForm()
	{
		global $ilCtrl;
		
		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->setTitle($this->getPluginObject()->txt('form_migration_init'));
		
		$ref = new ilNumberInputGUI($this->getPluginObject()->txt('source_ref_id'), 'ref');
		$ref->setRequired(true);
		$ref->setInfo($this->getPluginObject()->txt('source_ref_info'));
		$ref->setMinValue(1);
		$ref->setSize(7);
		$ref->setMaxLength(11);
		
		$form->addItem($ref);
		
		$form->addCommandButton('saveMigrationSourceSelection', $this->getPluginObject()->txt('btn_save_source_selection'));
		
		return $form;
	}
	
	/**
	 * Show migration form
	 * @return void
	 */
	protected function saveMigrationSourceSelection()
	{
		$form = $this->initMigrationForm();
		if(!$form->checkInput())
		{
			$form->setValuesByPost();
			ilUtil::sendFailure($GLOBALS['lng']->txt('err_check_input'));
			return $this->initMigration($form);
		}
		
		// save parameter source ref for next steps
		$GLOBALS['ilCtrl']->setParameter($this, 'ref', (int) $form->getInput('ref'));
		$GLOBALS['ilCtrl']->redirect($this, 'showMigrationSelection');
	}
	
	/**
	 * Show migration selection 
	 */
	protected function showMigrationSelection()
	{
		global $ilTabs;
		
		$ilTabs->activateTab('migration');
		
		if(!(int) $_REQUEST['ref'])
		{
			ilUtil::sendFailure($GLOBALS['lng']->txt('err_check_input'), true);
			$GLOBALS['ilCtrl']->redirect($this, 'initMigration');
			return false;
		}
		else
		{
			$GLOBALS['ilCtrl']->setParameter($this, 'ref', (int) $_REQUEST['ref']);
		}
		
		
		// show selection table
		$table = new ilFhoevMigrationSelectionTableGUI($this, 'showMigrationSelection', 'fhoev_migration');
		$table->enableObjectPath(true);
		$table->enableRowSelectionInput(true);
		$table->init();
		
		
		$node = $GLOBALS['tree']->getNodeData((int) $_REQUEST['ref']);
		$objects = $GLOBALS['tree']->getSubTree($node, false, array('crs'));
		
		include_once './Services/Tree/classes/class.ilPathGUI.php';
		$path = new ilPathGUI();
		$path->enableTextOnly(false);
		$table->customizePath($path);
		
		$table->setObjects((array) $objects);
		$table->parse();
		
		$GLOBALS['tpl']->setContent($table->getHTML());
	}
	
	protected function doMigration()
	{
		$GLOBALS['ilCtrl']->setParameter($this, 'ref', (int) $_REQUEST['ref']);
		if(!is_array($_REQUEST['id']))
		{
			ilUtil::sendFailure($GLOBALS['lng']->txt('select_one'),true);
			$GLOBALS['ilCtrl']->redirect($this, 'showMigrationSelection');
		}
		
		$table = new ilFhoevMigrationSelectionTableGUI($this, 'showMigrationSelection', 'fhoev_migration');
		$table->enableObjectPath(true);
		$table->enableRowSelectionInput(true);
		$table->init();
		
		$table->setObjects($_REQUEST['id']);
		$table->migrate();
		
		ilUtil::sendSuccess($this->getPluginObject()->txt('migrated_courses'), true);
		$GLOBALS['ilCtrl']->redirect($this, 'showMigrationSelection');
	}

}
?>
