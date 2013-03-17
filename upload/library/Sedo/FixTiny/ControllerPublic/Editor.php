<?php
class Sedo_FixTiny_ControllerPublic_Editor extends XFCP_Sedo_FixTiny_ControllerPublic_Editor
{
	public function actionFastDialog()
	{
		$styleId = $this->_input->filterSingle('style', XenForo_Input::UINT);
		if ($styleId)
		{
			$this->setViewStateChange('styleId', $styleId);
		}

		$dialog = $this->_input->filterSingle('dialog', XenForo_Input::STRING);
		$viewParams = array();

		if ($dialog == 'fast_media')
		{
			$viewParams['sites'] = $this->_getBbCodeModel()->getAllBbCodeMediaSites();
		}

		$viewParams['javaScriptSource'] = XenForo_Application::$javaScriptUrl;

		return $this->responseView('XenForo_ViewPublic_Editor_Dialog', 'editor_dialog_' . $dialog, $viewParams);	
	}
	
}