<?php

class Sedo_FixTiny_Listener_BbCode
{
	public static function listen($class, array &$extend)
	{
		if ($class == 'XenForo_BbCode_Formatter_Wysiwyg')
        	{
			/*
				#Fix Pacman effect with ol/ul with TinyMCE editing
				#Fix for tabs (From database to ToHtml editor || From Standard Editor to Rte Editor)
			*/
			$extend[] = 'Sedo_FixTiny_BbCode_Formatter_Wysiwyg';
		}
	}
}
//Zend_Debug::dump($class);