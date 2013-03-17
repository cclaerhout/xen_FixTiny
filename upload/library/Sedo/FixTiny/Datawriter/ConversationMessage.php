<?php
class Sedo_FixTiny_Datawriter_ConversationMessage extends XFCP_Sedo_FixTiny_Datawriter_ConversationMessage
{
	protected function _preSave()
	{
		parent::_preSave();
		
		if(isset($this->_newData['xf_conversation_message']['message']))
		{
			//Fix for tabs: save real tabs in the database (Conversation)
			$this->_newData['xf_conversation_message']['message'] = preg_replace('# {4}#', "\t", $this->_newData['xf_conversation_message']['message']);
		}
	}
}
//Zend_Debug::dump($class);