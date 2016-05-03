<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Table/classes/class.ilTable2GUI.php';
include_once './Services/Object/classes/class.ilObjectTableGUI.php';
include_once './Services/Tree/classes/class.ilPathGUI.php';

/**
 * Settings for LO courses
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * @version $Id$
 */
class ilFhoevMigrationSelectionTableGUI extends ilObjectTableGUI
{
	private $plugin = null;
	private $main_courses = array();
	
	
	/**
	 * Constructor
	 * @param type $a_parent_obj
	 * @param type $a_parent_cmd
	 * @param type $a_id
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, $a_id)
	{
		parent::__construct($a_parent_obj, $a_parent_cmd, $a_id);
		$this->plugin = ilFhoevImportPlugin::getInstance();
		$this->customizePath(new ilPathGUI());
	}


	/**
	 * @return \ilFhoevImportPlugin
	 */
	protected function getPlugin()
	{
		return $this->plugin;
	}
	
	/**
	 * Customize path instance
	 * @param ilPathGUI $path
	 * @return \ilPathGUI
	 */
	public function customizePath(ilPathGUI $path)
	{
		$this->path = $path;
	}
	
	public function getPath()
	{
		return $this->path;
	}
	

	/**
	 * Init table
	 */
	public function init()
	{
		if($this->enabledRowSelectionInput())
		{
			$this->addColumn('','id','5px');
		}
		
		$this->addColumn($this->getPlugin()->txt('migration_original_course'),'origin','50%');
		$this->addColumn($this->getPlugin()->txt('migration_main_course'),'main_course','50%');
		
		$this->setOrderColumn('origin');
        $this->setRowTemplate("tpl.migration_selection_row.html",substr($this->getPlugin()->getDirectory(),2));
	}
	
	/**
	 * Parse objects
	 */
	public function parse()
	{
		$this->parseMainCourses();
		
		$counter = 0;
		$set = array();
		foreach($this->getObjects() as $ref_id)
		{
			$set[$counter]['ref_id'] = $ref_id;
			$set[$counter]['obj_id'] = ilObject::_lookupObjId($ref_id);
			$set[$counter]['type'] = ilObject::_lookupType(ilObject::_lookupObjId($ref_id));
			$set[$counter]['title'] = ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id));
			
			$set[$counter]['childs'] = $this->parseChilds($ref_id);
			
			$title = ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id));
			$title_parts = explode(' ', $title);
			
			$set[$counter]['main_course'] = 0;
			if($title_parts[0] && $title_parts[1] && $title_parts[2])
			{
				$main_course_obj_id = $this->getMainCourse($title_parts[0].' '.$title_parts[1].' '.$title_parts[2]);
				if($main_course_obj_id)
				{
					$set[$counter]['main_course'] = $main_course_obj_id;
				}
			}
			
			
			$counter++;
		}
		$this->setData($set);
	}
	
	/**
	 * fill table rows
	 * @param type $set
	 */
	public function fillRow($set)
	{
		include_once './Services/Link/classes/class.ilLink.php';
		
		if($this->enabledRowSelectionInput())
		{
			$this->fillRowSelectionInput($set);
		}
		
		$this->tpl->setVariable('OBJ_LINK',ilLink::_getLink($set['ref_id'], $set['type']));
		$this->tpl->setVariable('OBJ_LINKED_TITLE',$set['title']);
		
		$this->tpl->setVariable('TXT_NUM_CHILDS', $this->getPlugin()->txt('num_childs'));
		$this->tpl->setVariable('NUM_CHILDS', $set['childs']);
		
		if($set['main_course'])
		{
			$obj_id = $set['main_course'];
			$ref_ids = ilObject::_getAllReferences($obj_id);
			$ref_id = end($ref_ids);
			$this->tpl->setVariable('MAIN_OBJ_LINK', ilLink::_getLink($ref_id));
			$this->tpl->setVariable('MAIN_OBJ_LINKED_TITLE', ilObject::_lookupTitle($obj_id));
			
			if($this->enabledObjectPath())
			{
				$path_gui = $this->getPath();

				$this->tpl->setCurrentBlock('main_path');
				$this->tpl->setVariable('MAIN_OBJ_PATH',$path_gui->getPath(ROOT_FOLDER_ID, $ref_id));
				$this->tpl->parseCurrentBlock();
			}
		}
		
		
		if($this->enabledObjectPath())
		{
			$path_gui = $this->getPath();
			
			$this->tpl->setCurrentBlock('path');
			$this->tpl->setVariable('OBJ_PATH',$path_gui->getPath(ROOT_FOLDER_ID, $set['ref_id']));
			$this->tpl->parseCurrentBlock();
		}
		
		
		
		
	}
	
	/**
	 * Fill row selection input
	 * @param type 
	 */
	public function fillRowSelectionInput($set)
	{
		if($set['main_course'])
		{
			$this->tpl->setCurrentBlock('row_selection_input');
			$this->tpl->setVariable('OBJ_INPUT_TYPE','checkbox');
			$this->tpl->setVariable('OBJ_INPUT_NAME','id[]');
			$this->tpl->setVariable('OBJ_INPUT_VALUE',$set['ref_id']);
			
		}
		else
		{
			$this->tpl->touchBlock('row_selection');
		}
	}
	
	protected function parseMainCourses()
	{
		global $ilDB;
		
		$query = 'SELECT title, od.obj_id FROM object_data od JOIN didactic_tpl_objs dto ON od.obj_id = dto.obj_id '.
			'WHERE type = '.$ilDB->quote('crs','text').' AND tpl_id > 0';
		$res = $ilDB->query($query);
		
		$this->main_courses = array();
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->main_courses[$row->obj_id] = $row->title;
		}
	}
	
	protected function parseChilds($a_ref_id)
	{
		$child_nodes = $GLOBALS['tree']->getChildIds($a_ref_id);
		if(count($child_nodes) > 1)
		{
			return count($child_nodes);
		}
		if(count($child_nodes) == 1)
		{
			// lookup rolf
			foreach($child_nodes as $child)
			{
				if(ilObject::_lookupType(ilObject::_lookupObjId($child)) == 'rolf')
				{
					return 0;
				}
			}
		}
		return 0;
	}


	protected function getMainCourse($a_title_part)
	{
		$res = array_search($a_title_part, $this->main_courses);
		if($res > 0)
		{
			return $res;
		}
		return 0;
	}
	
	
	
}