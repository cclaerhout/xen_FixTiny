<?php
class Sedo_FixTiny_Listener_ControllerPublic
{
	public static function listen($class, array &$extend)
	{
		if ($class == 'XenForo_ControllerPublic_Editor')
        	{
			$extend[] = 'Sedo_FixTiny_ControllerPublic_Editor';
		}
	}
}
//Zend_Debug::dump($class);