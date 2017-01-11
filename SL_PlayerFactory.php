<?php
final class SL_PlayerFactory
{
	public static function human(GWF_User $user)
	{
		if ($player = self::player($user))
		{
			self::shufflePlayer($player);
		}
		return $player;
	}
	
	public static function bot(GWF_User $user, $type)
	{
		if ($bot = self::player($user, 'SL_Bot', $type))
		{
			self::shuffleBot($bot);
		}
		return $bot;
	}
	
	##############
	### Create ###
	##############
	private static function player(GWF_User $user, $classname='SL_Player', $type=null)
	{
		$player = new $classname(array(
			'p_uid' => $user->getID(),
			'p_type' => $type,
			'p_race' => SL_Const::NONE,
			'p_gold' => '0',
			'p_hp' => '0',
			'p_mp' => '0',
			'p_base_hp' => '0',
			'p_base_mp' => '0',
			'p_strength' => '1',
			'p_dexterity' => '1',
			'p_wisdom' => '1',
			'p_intelligence' => '1',
			'p_fighter' => '0',
			'p_ninja' => '0',
			'p_priest' => '0',
			'p_wizard' => '0',
			'p_fighter_xp' => '0',
			'p_ninja_xp' => '0',
			'p_priest_xp' => '0',
			'p_wizard_xp' => '0',
			'p_active_color' => SL_Const::NONE,
			'p_active_element' => SL_Const::NONE,
			'p_active_skill' => SL_Const::NONE,
			'p_active_mode' => SL_Const::NONE,
			'p_last_color_change' => null,
			'p_last_element_change' => null,
			'p_last_skill_change' => null,
			'p_last_mode_change' => null,
			'p_last_activity' => null,
		));
		if (!$player->insert())
		{
			return false;
		}
		foreach (SL_Player::$USER_FIELDS as $field)
		{
			$player->setVar($field, $user->getVar($field));
		}
		$player->setUser($user);
		$player->afterLoad();
		return $player;
	}
	
	private static function shufflePlayer(SL_Player $player)
	{
		self::shuffleHPMP($player, SL_Global::rand(4, 6), SL_Global::rand(3, 6));
	}
	
	private static function shuffleHPMP(SL_Player $player, $hp, $mp)
	{
		$player->increaseVars(array(
			'p_base_hp' => $hp,
			'p_base_mp' => $mp,
		));
		$player->rehash();
		$player->giveHP($hp);
		$player->giveMP($mp);
	}

	private static function shuffleBot(SL_Bot $bot)
	{
		$ais = $bot->getScript();
		$bot->getUser()->saveVar('user_gender', $ais->random_gender());
		$bot->setVar('user_gender', $bot->getUser()->getGender());
		$bot->saveVars(array(
			'p_gold' => $ais->random_gold(),
			'p_race' => $ais->random_race(),
			'p_active_mode' => $ais->random_mode(),
			'p_active_color' => $ais->random_color(),
			'p_active_element' => $ais->random_element(),
		));
		self::shuffleHPMP($bot, $ais->random_hp(), $ais->random_mp());
		self::shuffleBotLevel($bot);
	}
	
	private static function shuffleBotLevel(SL_Bot $bot)
	{
		foreach (SL_Player::$SKILLS as $skill)
		{
			$level = call_user_func(array($bot->getScript(), 'random_'.$skill));
			$xp = SL_Levelup::levelXP($level);
			$bot->giveXP($skill, $xp, false);
		}
	}
	
}