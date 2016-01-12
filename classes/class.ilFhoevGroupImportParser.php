<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Group import parser
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilFhoevGroupImportParser extends ilFhoevImportParser
{
	protected $xmlElement = null;
	
	
	/**
	 * constructor
	 * @param type $a_file 
	 */
	public function __construct($a_file)
	{
		parent::__construct($a_file);
		
		$this->initSoapClient();
		$this->loginSoap();
		
	}
	

	/**
	 * Get path to xml file
	 * @return type 
	 */
	public function getXmlFile()
	{
		return $this->xml_file;
	}


	public function startParsing()
	{
		global $tree;

		$this->initSoapClient(300);
		$this->loginSoap();

		ilFhoevLogger::getLogger()->write('Parsing group data...');
		
		$this->xmlElement = simplexml_load_file($this->getXmlFile());
		
		foreach($this->xmlElement->group as $groupNode)
		{
			$group_parent_ref_id = 0;
			$group_ref_id = 0;
			
			$group_id = (string) $groupNode['Id'];
			$obj_id = $this->lookupObjId($group_id,'grp');

			$delete_flag = false;
			
			ilFhoevLogger::getLogger()->write('Handling group '. (string) $groupNode->Title . ' with id '. $group_id. '...');

			if($obj_id)
			{
				if($groupNode['Action'] == "Delete")
				{
					//Call delete group
					ilFhoevLogger::getLogger()->write('Group exists. Calling delete.');
					$delete_flag = true;
					$groupNode['importId'] = $group_id;
				}
				else
				{
					// call update group
					ilFhoevLogger::getLogger()->write('Group exists. Calling update.');
				}

				$groupNode['id'] = $obj_id;
				$group_ref_id = $this->getReferenceId($obj_id);
				if(!$group_ref_id)
				{
					ilFhoevLogger::getLogger()->write('ERROR: No reference id found for '. (string) $groupNode->title);
					$this->hasErrors = true;
					continue;
					//throw new ilFhoevIOException('No reference id found for '. (string) $groupNode->title);
				}

				$tmp_parent = $groupNode['parentId'];
				if($tmp_parent)
				{
					$group_parent_obj_id = $this->lookupObjId($tmp_parent,'cat');
					if(!$group_parent_obj_id)
					{
						ilFhoevLogger::getLogger()->write('ERROR: No parent category found for '. (string) $groupNode->title);
						$this->hasErrors = true;
						continue;
					}
					$group_parent_ref_id = $this->getReferenceId($group_parent_obj_id);
					if(!$group_parent_ref_id)
					{
						ilFhoevLogger::getLogger()->write('ERROR: No parent category ref_id found for '. (string) $groupNode->title);
						$this->hasErrors = true;
						continue;
					}

					if($tree->getParentId($group_ref_id) != $group_parent_ref_id)
					{
						ilFhoevLogger::getLogger()->write('Group Parent differs from actual parent. Move to new parent.');
						$tree->moveTree($group_ref_id, $group_parent_ref_id);
					}
				}
			}
			else
			{
				ilFhoevLogger::getLogger()->write('Group does not exist. Calling create.');
				$groupNode['importId'] = $group_id;
				$tmp_parent = $groupNode['parentId'];
				if(!$tmp_parent)
				{
					ilFhoevLogger::getLogger()->write('ERROR: Missing attribute parentId for '. (string) $groupNode->title);
					$this->hasErrors = true;
					continue;
					//throw new ilFhoevIOException('Missing attribute parentId for '. (string) $groupNode->Title);
				}

				$group_parent_obj_id = $this->lookupObjId($tmp_parent,'cat');
				if(!$group_parent_obj_id)
				{
					ilFhoevLogger::getLogger()->write('ERROR: No parent category found for '. (string) $groupNode->title);
					$this->hasErrors = true;
					continue;
					//throw new ilFhoevIOException('No parent category found for '. (string) $groupNode->title);
				}
				$group_parent_ref_id = $this->getReferenceId($group_parent_obj_id);
				if(!$group_parent_ref_id)
				{
					ilFhoevLogger::getLogger()->write('ERROR: No parent category ref_id found for '. (string) $groupNode->title);
					$this->hasErrors = true;
					continue;
					//throw new ilFhoevIOException('No parent category ref_id found for '. (string) $groupNode->title);
				}
			}
			
			// Members
			foreach($groupNode->member as $adminNode)
			{
				$admin_id = $this->lookupObjId($adminNode['id'], 'usr');
				if(!$admin_id)
				{
					ilFhoevLogger::getLogger()->write('WARNING: Member not found for '. (string) $groupNode->title. ' '. (string) $adminNode['id']);
					$this->hasErrors = true;
					$this->removeNode($adminNode);
					continue;
					#throw new ilFhoevIOException('Member not found for '. (string) $groupNode->title. ' '. (string) $adminNode['id']);
				}
				$adminNode['id'] = 'il_'.IL_INST_ID.'_usr_'.$admin_id;
			}
			// Admins
			foreach($groupNode->admin as $adminNode)
			{
				$admin_id = $this->lookupObjId($adminNode['id'], 'usr');
				if(!$admin_id)
				{
					ilFhoevLogger::getLogger()->write('WARNING: Administrator not found for '. (string) $groupNode->title. ' '. (string) $adminNode['id']);
					$this->hasErrors = true;
					$this->removeNode($adminNode);
					continue;
					#throw new ilFhoevIOException('Administrator not found for '. (string) $groupNode->title. ' '. (string) $adminNode['id']);
				}
				$adminNode['id'] = 'il_'.IL_INST_ID.'_usr_'.$admin_id;
			}

			unset($groupNode['Id']);
			unset($groupNode['parentId']);
			unset($groupNode['Action']);



			if(!count($groupNode->admin)&& !$delete_flag)
			{
				ilFhoevLogger::getLogger()->write('ERROR: No admin found for '. (string) $groupNode->title);
				$this->hasErrors = true;
				continue;
			}

			if($obj_id && $delete_flag)
			{
				$this->deleteGroupSoap($groupNode['importId']);
			}
			elseif($obj_id)
			{
				$this->updateGroupSoap($group_ref_id,$groupNode->saveXML());
			}
			else
			{
				$this->addGroupSoap($group_parent_ref_id,$groupNode->saveXML());
			}
		}
		$this->logoutSoap();
	}
	
	/**
	 * Update group soap
	 * @param type $ref_id
	 * @param type $xml 
	 */
	protected function updateGroupSoap($ref_id, $xml)
	{
		ilFhoevLogger::getLogger()->write('Using xml : '. $xml);
		$res = $this->getSoapClient()->call(
				'updateGroup',
				array(
					$this->soap_session,
					$ref_id,
					$xml
				)
			);
		ilFhoevLogger::getLogger()->write('Update group response is: '. $res);
	}
	
	/**
	 * Add group soap
	 * @param type $parent
	 * @param type $xml 
	 */
	protected function addGroupSoap($parent, $xml)
	{
		ilFhoevLogger::getLogger()->write('Using xml : '. $xml);
		$res = $this->getSoapClient()->call(
				'addGroup',
				array(
					$this->soap_session,
					$parent,
					$xml
				)
			);
		ilFhoevLogger::getLogger()->write('Update group response is: '. $res);
		
	}

	/**
	 * Delete group soap
	 * @param string $import_id
	 */
	protected function deleteGroupSoap($import_id)
	{
		//ilFhoevLogger::getLogger()->write('Using xml : '. $xml);
		$res = $this->getSoapClient()->call(
			'removeFromSystemByImportId',
			array(
				$this->soap_session,
				$import_id
			)
		);
		ilFhoevLogger::getLogger()->write('Delete object response is: '. $res);

	}
	
	

	/**
	 * Begin tag
	 * @param type $a_xml_parser
	 * @param type $a_name
	 * @param type $a_attribs 
	 */
	protected function handlerBeginTag($a_xml_parser, $a_name, $a_attribs) 
	{
		
		// no sax parsing for users
		
	}
	
	protected function handlerEndTag($a_xml_parser, $a_name) 
	{
		// no sax parser for users
	}
	
}
?>