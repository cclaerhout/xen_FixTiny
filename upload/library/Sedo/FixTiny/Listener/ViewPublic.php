<?php
class Sedo_FixTiny_Listener_ViewPublic
{
	public static function listen($class, array &$extend)
	{
		if ($class == 'XenForo_ViewPublic_Editor_ToBbCode')
        	{
			//Fix included: [Tiny space tabs]=> [Real tabs inside standard editor]
			$extend[] = 'Sedo_FixTiny_ViewPublic_Editor_ToBbCode';
		}

		if ($class == 'XenForo_ViewPublic_Editor_ToHtml')
        	{
			//Listen text with bbcodes just before they are modified to html
			$options = XenForo_Application::get('options');
			if($options->tinymce_fix_tagspolution_prepaser_active)
			{
				$extend[] = 'Sedo_FixTiny_ViewPublic_Editor_ToHtml';			
			}
		}
	}
}
//Zend_Debug::dump($class);