<?php
	class Sedo_FixTiny_Options_Factory
	{
		public static function check_mobile(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
		{		
			$mobile_options = array();
			$mobile_options['no'] = new XenForo_Phrase('tinymce_fix_mobile_no');
			$mobile_options['yes'] = new XenForo_Phrase('tinymce_fix_mobile_yes');

			if(class_exists('Sedo_DetectBrowser_Listener_Visitor'))
            		{
				$mobile_options['tabletsonly'] = new XenForo_Phrase('tinymce_fix_mobile_tabletsonly');
			}

			$preparedOption['formatParams'] = $mobile_options;
	
			return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_select', $view, $fieldPrefix, $preparedOption, $canEdit);
		}
	}

