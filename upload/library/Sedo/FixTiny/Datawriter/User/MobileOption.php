<?php
class Sedo_FixTiny_DataWriter_User_MobileOption extends XFCP_Sedo_FixTiny_DataWriter_User_MobileOption
{
	protected function _getFields() 
	{
		$parent = parent::_getFields();
		$parent['xf_user_option']['tinyfix_rte_mobile'] = array(
				'type' => self::TYPE_BOOLEAN, 
				'default' => 1
		);

		return $parent;
	}

	protected function _preSave()
	{
		$options = XenForo_Application::get('options');
		$_input = new XenForo_Input($_REQUEST);

		if($options->tinymce_fix_mobile == 'no')
		{
			return parent::_preSave();
		}

		if($_input->inRequest('tinyfix_rte_mobile'))
		{
			$mobileOption = $_input->filterSingle('tinyfix_rte_mobile', XenForo_Input::UINT);
			$mobileOption = ($options->tinymce_fix_mobile_opt_reverse) ? !$mobileOption : $mobileOption;
			
			$this->set('tinyfix_rte_mobile', $mobileOption);
		}

		return parent::_preSave();
	}
}

