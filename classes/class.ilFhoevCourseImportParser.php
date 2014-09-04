<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Course import parser
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilFhoevCourseImportParser extends ilFhoevImportParser
{
	protected $xmlElement = null;
	
	protected $hasErrors = false;
	
	
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
		$this->initSoapClient(300);
		$this->loginSoap();

		ilFhoevLogger::getLogger()->write('Parsing course data...');
		
		$this->xmlElement = simplexml_load_file($this->getXmlFile());

		foreach($this->xmlElement->Course as $courseNode)
		{
			$course_parent_ref_id = 0;
			$course_ref_id = 0;
			
			$course_id = (string) $courseNode['Id'];
			$obj_id = $this->lookupObjId($course_id,'crs');
			
			ilFhoevLogger::getLogger()->write('Handling course '. (string) $courseNode->MetaData->General->Title . ' with id '. $course_id. '...');

			if($obj_id)
			{
				// call update course
				ilFhoevLogger::getLogger()->write('Course exists. Calling update.');
				$courseNode['id'] = $obj_id;
				$course_ref_id = $this->getReferenceId($obj_id);
				if(!$course_ref_id)
				{
					$this->hasErrors = true;
					ilFhoevLogger::getLogger()->write('ERROR: No reference id found for '. (string) $courseNode->MetaData->General->Title);
					//throw new ilFhoevIOException('No reference id found for '. (string) $courseNode->MetaData->General->Title);
					continue;
				}
			}
			else
			{
				ilFhoevLogger::getLogger()->write('Course does not exist. Calling create.');
				$courseNode['importId'] = $course_id;
				$tmp_parent = $courseNode['parentId'];
				if(!$tmp_parent)
				{
					ilFhoevLogger::getLogger()->write('ERROR: Missing attribute parentId for '. (string) $courseNode->MetaData->General->Title);
					$this->hasErrors = true;
					continue;
					//throw new ilFhoevIOException('Missing attribute parentId for '. (string) $courseNode->MetaData->General->Title);
				}

				$course_parent_obj_id = $this->lookupObjId($tmp_parent,'cat');
				if(!$course_parent_obj_id)
				{
					ilFhoevLogger::getLogger()->write('ERROR: No parent category found for '. (string) $courseNode->MetaData->General->Title);
					$this->hasErrors = true;
					continue;
					//throw new ilFhoevIOException('No parent category found for '. (string) $courseNode->MetaData->General->Title);
				}
				$course_parent_ref_id = $this->getReferenceId($course_parent_obj_id);
				if(!$course_parent_ref_id)
				{
					ilFhoevLogger::getLogger()->write('ERROR: No parent category ref_id found for '. (string) $courseNode->MetaData->General->Title);
					$this->hasErrors = true;
					continue;
					//throw new ilFhoevIOException('No parent category ref_id found for '. (string) $courseNode->MetaData->General->Title);
				}
			}
			
			// Members
			foreach($courseNode->Member as $adminNode)
			{
				$admin_id = $this->lookupObjId($adminNode['id'], 'usr');
				if(!$admin_id)
				{
					ilFhoevLogger::getLogger()->write('WARNING: Member not found for '. (string) $courseNode->MetaData->General->Title. ' '. (string) $adminNode['id']);
					$this->hasErrors = true;
					$this->removeNode($adminNode);
					continue;
					//throw new ilFhoevIOException('Member not found for '. (string) $courseNode->MetaData->General->Title. ' '. (string) $adminNode['id']);
				}
				$adminNode['id'] = 'il_'.IL_INST_ID.'_usr_'.$admin_id;
			}
			// Tutors
			foreach($courseNode->Tutor as $adminNode)
			{
				$admin_id = $this->lookupObjId($adminNode['id'], 'usr');
				if(!$admin_id)
				{
					ilFhoevLogger::getLogger()->write('WARNING: Tutor not found for '. (string) $courseNode->MetaData->General->Title. ' '. (string) $adminNode['id']);
					$this->hasErrors = true;
					$this->removeNode($adminNode);
					continue;
					//throw new ilFhoevIOException('Tutor not found for '. (string) $courseNode->MetaData->General->Title. ' '. (string) $adminNode['id']);
					
				}
				$adminNode['id'] = 'il_'.IL_INST_ID.'_usr_'.$admin_id;
			}
			// Admins
			foreach($courseNode->Admin as $adminNode)
			{
				$admin_id = $this->lookupObjId($adminNode['id'], 'usr');
				if(!$admin_id)
				{
					ilFhoevLogger::getLogger()->write('WARNING: Administrator not found for '. (string) $courseNode->MetaData->General->Title. ' '. (string) $adminNode['id']);
					$this->hasErrors = true;
					$this->removeNode($adminNode);
					continue;
					//throw new ilFhoevIOException('Administrator not found for '. (string) $courseNode->MetaData->General->Title. ' '. (string) $adminNode['id']);
				}
				$adminNode['id'] = 'il_'.IL_INST_ID.'_usr_'.$admin_id;
			}

			unset($courseNode['Id']);
			unset($courseNode['parentId']);
			unset($courseNode['Action']);
			
			$hasAdmin = count($courseNode->Admin);
			if(!$hasAdmin)
			{
				ilFhoevLogger::getLogger()->write('ERROR: No administrator found for '. (string) $courseNode->MetaData->General->Title);
				$this->hasErrors = true;
				continue;
			}
			
			if($obj_id)
			{
				$this->updateCourseSoap($course_ref_id,$courseNode->saveXML());
			}
			else
			{
				$this->addCourseSoap($course_parent_ref_id,$courseNode->saveXML());
			}
		}
		
		$this->logoutSoap();
	}
	
	/**
	 * Update course soap
	 * @param type $ref_id
	 * @param type $xml 
	 */
	protected function updateCourseSoap($ref_id, $xml)
	{
		ilFhoevLogger::getLogger()->write('Using xml : '. $xml);
		$res = $this->getSoapClient()->call(
				'updateCourse',
				array(
					$this->soap_session,
					$ref_id,
					$xml
				)
			);
		ilFhoevLogger::getLogger()->write('Update course response is: '. $res);
	}
	
	/**
	 * Add Course soap
	 * @param type $parent
	 * @param type $xml 
	 */
	protected function addCourseSoap($parent, $xml)
	{
		ilFhoevLogger::getLogger()->write('Using xml : '. $xml);
		$res = $this->getSoapClient()->call(
				'addCourse',
				array(
					$this->soap_session,
					$parent,
					$xml
				)
			);
		ilFhoevLogger::getLogger()->write('Update course response is: '. $res);
		
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
