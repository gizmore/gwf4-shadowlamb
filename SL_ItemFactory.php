<?php
final class SL_ItemFactory
{
	private static $ACTIONS;
	private static $ITEM_ACTIONS;
	public static function actionNames() { if (!self::$ACTIONS) self::init(); return self::$ACTIONS; }
	
	private static function sl() { return Module_Shadowlamb::instance(); }
	private static function actionPath() { return self::sl()->getModuleFilePath('item/itemactions.php'); }
	private static function actionClassPath() { return self::sl()->getModuleFilePath('action'); }
	
	public static function itemActions(SL_Item $item)
	{
		$itemName = $item->getName();
		return isset(self::$ITEM_ACTIONS[$itemName]) ? self::$ITEM_ACTIONS[$itemName] : array();
	}
	
	public static function init()
	{
		self::$ACTIONS = array();
		GWF_File::filewalker(self::actionClassPath(), function($entry, $path) {
			$action = substr($entry, 3, strlen($entry)-13);
			self::$ACTIONS[] = $action;
			require_once $path;
		});
		
		self::$ITEM_ACTIONS = require_once self::actionPath();
		self::$ITEM_ACTIONS = array_map(function($n) { return explode(',', $n); }, self::$ITEM_ACTIONS);
		foreach (self::$ITEM_ACTIONS as $itemName => $actions)
		{
			foreach ($actions as $action)
			{
				if (!in_array($action, self::$ACTIONS, true))
				{
					self::$ACTIONS[] = $action;
				}
			}
		}
		
	}
	
	public static function actionToID($action)
	{
		$index = array_search($action, self::$ACTIONS);
		return $index === false ? 0 : $index + 1;
	}
	
	public static function idToAction($actionId)
	{
		return isset(self::$ACTIONS[$actionId-1]) ? self::$ACTIONS[$actionId-1] : null;
	}
	
	public static function actionClassname($actionId)
	{
		if ($action = self::idToAction($actionId))
		{
			$classname = sprintf('SL_%sAction', $action);
			if (class_exists($classname))
			{
				return $classname;
			}
		}
		return null;
	}
}
