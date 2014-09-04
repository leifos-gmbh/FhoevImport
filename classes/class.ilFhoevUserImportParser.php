<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Course import parser
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilFhoevUserImportParser extends ilFhoevImportParser
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
		ilFhoevLogger::getLogger()->write('Parsing user data...');
		
		$this->xmlElement = simplexml_load_file($this->getXmlFile());
		
		foreach($this->xmlElement->User as $userNode)
		{
			$user_id = (string) $userNode['Id'];
			$obj_id = $this->lookupObjId($user_id,'usr');
			
			ilFhoevLogger::getLogger()->write('Handling user '. (string) $userNode->Login . ' with id '. $user_id. '...');

			if($obj_id)
			{
				if($userNode['Action'] != 'Delete')
				{
					ilFhoevLogger::getLogger()->write('Setting action to "Update"');
					$userNode['Action'] = 'Update';
					$userNode['Id'] = $obj_id;
				}
				else
				{
					ilFhoevLogger::getLogger()->write('Using action "Delete"');
					$userNode['Id'] = $obj_id;
					
					// Delete all role assignments
					foreach($userNode->Role as $roleNode)
					{
						$this->removeNode($roleNode);
					}
					
				}
			}
			else
			{
				ilFhoevLogger::getLogger()->write('Setting action to "Insert"');
				$userNode['Action'] = 'Insert';
			}
			
			// 
			
		}
		
		// Write to soap
		$this->initSoapClient(3600);
		$this->loginSoap();
		
		$res = $this->getSoapClient()->call(
				'importUsers',
				array(
					$this->soap_session,
					USER_FOLDER_ID,
					$this->xmlElement->saveXML(),
					2,
					0
				)
			);
		
		ilFhoevLogger::getLogger()->write('User import repsonse is: '. $res);
		
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
