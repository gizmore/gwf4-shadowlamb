<?php
final class SL_Global
{
	private static $INITIAL_SEED = 0;
	public static $SEED = 31337;
	public static $TICK = 0;
	public static $BOTS = array(), $HUMANS = array(), $PLAYERS = array(), $TYPED_BOTS = array();
	public static $AVG_BASE = array();
	public static $AVG_ADJUST = array();
	
	public static $GAMES;
	
	public static function init($seed, $handler)
	{
		self::$TICK = 0;
		self::$INITIAL_SEED = self::$SEED = $seed;
		self::$BOTS = array(); self::$HUMANS = array(); self::$PLAYERS = array();
		foreach (SL_AIScript::init() as $type)
		{
			self::$TYPED_BOTS[$type] = array();
		}
		self::$GAMES = new SL_Games($handler);
	}

	############
	### Game ###
	############
	public static function tick()
	{
		self::$TICK++;
		self::$AVG_BASE = self::$AVG_ADJUST = array();
		self::$GAMES->tick(self::$TICK);
	}
	
	public static function rand($min, $max)
	{
		return GWF_Random::rand($min, $max);
	}
	
	public static function randItem(array $array)
	{
		return $array[array_rand($array)];
	}
	
	public static function gameByName($name)
	{
		return self::$GAMES->getGame($name);
	}
	
	###############
	### Players ###
	###############
	public static function addPlayer(SL_Player $player)
	{
		$id = $player->getID();
		if ($player->isBot())
		{
			self::$BOTS[$id] = $player;
			self::$TYPED_BOTS[$player->getType()][$id] = $player;
		}
		else
		{
			self::$HUMANS[$id] = $player;
		}
		self::$PLAYERS[$id] = $player;
	}

	public static function removePlayer(SL_Player $player)
	{
		$id = $player->getID();
		if ($player->isBot())
		{
			unset(self::$BOTS[$id]);
			unset(self::$TYPED_BOTS[$player->getType()][$id]);
		}
		else
		{
			unset(self::$HUMANS[$id]);
		}
		unset(self::$PLAYERS[$id]);
	}
	
	public static function getOrCreatePlayer(GWF_User $user)
	{
		$id = $user->getID();
		if (!($player = self::getOrLoadPlayer($id)))
		{
			$player = self::createPlayer($user);
			self::addPlayer($player);
		}
		$player->setUser($user);
		return $player;
	}
	
	public static function getPlayer($id)
	{
		return isset(self::$PLAYERS[$id]) ? self::$PLAYERS[$id] : false;
	}
	
	public static function getOrLoadPlayer($id)
	{
		if ($player = self::getPlayer($id))
		{
			return $player;
		}
		if ($player = self::loadPlayer($id))
		{
			self::$PLAYERS[$id] = self::$HUMANS[$id] = $player;
			return $player;
		}
		return false;
	}

	private static function createPlayer(GWF_User $user)
	{
		return SL_PlayerFactory::human($user);
	}
	
	private static function loadPlayer($id)
	{
		return SL_Player::getByID($id);
	}
	
	###############
	### Average ###
	###############
	public static function averageBase($field)
	{
		if (!isset(self::$AVG_BASE[$field]))
		{
			$total = 1;
			$count = count(self::$HUMANS) + 1;
			foreach (self::$HUMANS as $player)
			{
				$total += $player->base($field);
			}
			self::$AVG_BASE[$field] = round($total / $count);
		}
		return self::$AVG_BASE[$field];
	}
	
	public static function averagePower($field)
	{
		if (!isset(self::$AVG_ADJUST[$field]))
		{
			self::$AVG_ADJUST[$field] = 0;
			$count = count(self::$HUMANS);
			if ($count > 0)
			{
				$total = 0;
				foreach (self::$HUMANS as $player)
				{
					$total += $player->power($field);
				}
				self::$AVG_ADJUST[$field] = round($total / $count);
			}
		}
		return self::$AVG_ADJUST[$field];
	}
}
