<?php
class Sedo_FixTiny_BbCode_Formatter_Wysiwyg extends XFCP_Sedo_FixTiny_BbCode_Formatter_Wysiwyg
{
	public function filterFinalOutput($output)
	{
		$parent = parent::filterFinalOutput($output);
		$options = XenForo_Application::get('options');
		$emptyParaText = (XenForo_Visitor::isBrowsingWith('ie') ? '&nbsp;' : '<br />');

		if(!empty($options->tinymce_fix_pacman_olul))
		{
			//Fix Pacman effect with ol/ul with TinyMCE editing
			$parent = preg_replace('#(</(ul|ol)>)\s</p>#', '$1<p>' . $emptyParaText . '</p>', $parent);
		}

		if(!empty($options->tinymce_fix_housewife_tabs))
		{   
			//Fix for tabs (From database to ToHtml editor || From Standard Editor to Rte Editor)
			$parent = preg_replace('#\t#', '&nbsp;&nbsp;&nbsp;&nbsp;', $parent);
		}

		return $parent;
	}
}
//Zend_Debug::dump($parent);
