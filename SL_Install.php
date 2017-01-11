<?php
final class SL_Install
{
	public static function onInstall(Module_Shadowlamb $module, $dropTable)
	{
		return GWF_ModuleLoader::installVars($module, array(
		));
	}
}