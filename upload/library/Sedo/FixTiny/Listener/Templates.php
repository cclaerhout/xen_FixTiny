<?php
class Sedo_FixTiny_Listener_Templates
{
	public static function listenhooks($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName) 
		{
		case 'editor_js_setup':
			$options = XenForo_Application::get('options');
			
			//Disable the line tinyMCE.dom.Event._pageInit
			if(!empty($options->tinymce_fix_overlay))
			{
				$contents = str_replace('tinyMCE.dom.Event._pageInit', '//tinyMCE.dom.Event._pageInit', $contents);
			}

			
			//Check if the XenForo Elastic must be disabled (if mobile => disable)
			$killElastic = false;
			
			if($options->tinymce_fix_mobile == 'no')
			{
				break;
			}
			elseif($options->tinymce_fix_mobile == 'yes' && XenForo_Visitor::isBrowsingWith('mobile'))
			{
				$killElastic = true;
			}
			elseif($options->tinymce_fix_mobile == 'tabletsonly')
			{
				$visitor = XenForo_Visitor::getInstance();
				if(!$visitor->getBrowser['isTablet'])
				{
					break;
				}
					
				$killElastic = true;
			}
			
			if($killElastic === true)
			{
				$contents = str_replace(',-xenforo_elastic', '', $contents);
			}

			break;

		case 'editor_tinymce_init':
			$options = XenForo_Application::get('options');	
			$params = "$1fast_overlay: '" . $options->tinymce_fix_popup_overlay . "',";

			$contents = preg_replace("#(\s+?)document_base_url(?:\s+?)?:.+,#i", "$0$params", $contents);

			break;
		}
	}
}
//Zend_Debug::dump($contents);