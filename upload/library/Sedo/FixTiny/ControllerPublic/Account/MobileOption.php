<?php
class Sedo_FixTiny_ControllerPublic_Account_MobileOption extends XFCP_Sedo_FixTiny_ControllerPublic_Account_MobileOption
{
	public function actionPreferencesSave()
	{
		$this->_assertPostOnly();
		$mobileOption = $this->_input->filterSingle('tinyfix_rte_mobile', XenForo_Input::UINT);

		$options = XenForo_Application::get('options');
			
		if ($options->tinymce_fix_mobile_opt_reverse)
		{
			$mobileOption = !$mobileOption;
		}


		$dw = XenForo_DataWriter::create('XenForo_DataWriter_User');
		$dw->setExistingData(XenForo_Visitor::getUserId());
		$dw->set('tinyfix_rte_mobile', $mobileOption);
		$dw->preSave();
		if ($dwErrors = $dw->getErrors())
		{
			return $this->responseError($dwErrors);
		}
		$dw->save();

		return parent::actionPreferencesSave();
	}
}


