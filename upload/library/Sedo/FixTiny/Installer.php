<?php
class Sedo_FixTiny_Installer
{
	public static function install($addon)
	{
		$db = XenForo_Application::get('db');
		
		if(empty($addon) || $addon['version_id'] < 12)
		{
			self::addColumnIfNotExist($db, 'xf_user_option', 'tinyfix_rte_mobile', 'TINYINT UNSIGNED NOT NULL DEFAULT 1');
		}
		elseif($addon['version_id'] < 13)
		{
			//Not sure if needed but should fix the problem for those who had it
			self::changeColumnValueIfExist($db, 'xf_user_option', 'tinyfix_rte_mobile', 'TINYINT UNSIGNED NOT NULL DEFAULT 1');		
		}
		
		if(!empty($addon) && $addon['version_id'] < 14)
		{
			$db->query("UPDATE xf_user_option SET tinyfix_rte_mobile = '1'");
		}
	}
 
	//Src: http://xenforo.com/community/threads/try-catch-not-working.28318/#post-330450
	public static function addColumnIfNotExist($db, $table, $field, $attr)
	{
		if ($db->fetchRow("SHOW COLUMNS FROM $table WHERE Field = ?", $field))
		{
			return;
		}
	 
		return $db->query("ALTER TABLE $table ADD $field $attr");
	}
	
	public static function changeColumnValueIfExist($db, $table, $field, $attr)
	{
		if (!$db->fetchRow("SHOW COLUMNS FROM $table WHERE Field = ?", $field))
		{
			return;
		}

		return $db->query("ALTER TABLE $table CHANGE $field $field $attr");
	}

	public static function uninstall()
	{
		XenForo_Application::get('db')->query("ALTER TABLE xf_user_option DROP tinyfix_rte_mobile");	
	}
}
?>

