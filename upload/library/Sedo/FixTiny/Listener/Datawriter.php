<?php
class Sedo_FixTiny_Listener_Datawriter
{
	public static function listen($class, array &$extend)
	{
		if ($class == 'XenForo_DataWriter_DiscussionMessage_Post')
        	{
			//Fix for tabs: save real tabs in the database (Posts)
        		$options = XenForo_Application::get('options');

			if(!empty($options->tinymce_fix_housewife_tabs_db))
			{        		
				$extend[] = 'Sedo_FixTiny_Datawriter_DiscussionMessage';
			}
		}
		
		if($class == 'XenForo_DataWriter_ConversationMessage')
		{
			//Fix for tabs: save real tabs in the database (Conversation)
			$options = XenForo_Application::get('options');
			
			if(!empty($options->tinymce_fix_housewife_tabs_db))
			{
				$extend[] = 'Sedo_FixTiny_Datawriter_ConversationMessage';
			}
		}
		
		if($class == 'XenForo_DataWriter_User')
		{
			//User option if option to display MCE on mobile has been selected
			$options = XenForo_Application::get('options');
			
			if ($options->tinymce_fix_mobile == 'yes')
			{
				$extend[] = 'Sedo_FixTiny_Datawriter_User_MobileOption';
			}
		}		
		
		
	}
}
//Zend_Debug::dump($class);