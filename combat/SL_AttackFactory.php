<?php
require_once 'SL_Attack.php';
require_once 'SL_ThrowAttack.php';
require_once 'SL_KickAttack.php';

final class SL_AttackFactory
{
	const PUNCH = 1;
	const KICK = 2;
	const WARCRY = 3;
	const SLASH = 4;
	const MELEE = 5;
	const STAB = 6;

	public static function isValidType(SL_Item $weapon, $attackType)
	{
		return true;
	}
	
	public static function attack(SL_Player $player, SL_Player $defender, $attackType, SL_Item $weapon)
	{
		switch ($attackType)
		{
			default:
		}
		
	}
	
}
