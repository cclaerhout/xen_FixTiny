<?php
class Sedo_FixTiny_Listener_ControllerPreDispatch
{
	public static function Diktat(XenForo_Controller $controller, $action)
	{
		$options = XenForo_Application::get('options');
		
		if(isset($options->tinymce_fix_mobile) && $options->tinymce_fix_mobile != 'non' && $options->tinymce_fix_mobile_disableoverlay &&
			XenForo_Visitor::isBrowsingWith('mobile') && XenForo_Visitor::getInstance()->enable_rte)
		{
			//No matter here mobiles or only tablets, stwich off the overlay edit
			$options->messageInlineEdit = 0;
		}
	}
}