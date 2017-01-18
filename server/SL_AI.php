<?php
require_once 'SL_AIScript.php';

final class SL_AI
{
	private $game;
	private $spawncounter = 0;
	private $lastSpawn = null;
	private $scripts;
	
	###############
	### Getters ###
	###############
	public function handler() { return $this->handler; }
	public function tgc() { return Module_Shadowlamb::instance(); }
	public function bots() { return $this->game->bots(); }
	public function humans() { return $this->game->humans(); }
	public function players() { return $this->game->players(); }
	public function maxBots() { return $this->tgc()->cfgMaxBots(); }
	public function allowBots() { return $this->tgc()->cfgBots(); }
	
	############
	### Load ###
	############
	public function init($game)
	{
		$this->game = $game;
		$this->spawncounter = 0;
		$this->scripts = SL_AIScript::init();
		if ($this->allowBots())
		{
			$this->cleanup();
			$this->loadBots();
		}
	}
	
	public function loadBots()
	{
		$table = GDO::table('SL_Bot');
		$result = $table->select(SL_Player::userFields(), 'p_type IS NOT NULL', '', SL_Player::$JOINS);
		while ($bot = $table->fetch($result, GDO::ARRAY_O))
		{
			$bot instanceof SL_Bot;
			$bot->setUser(new GWF_User($bot->getGDOData()));
			$this->addBot($bot);
			$bot->afterLoad();
		}
	}
	
	public function cleanup()
	{
		$table = GDO::table('SL_Bot');
		$where = 'p_type IS NOT NULL';
		$max = $this->maxBots();
		$have = $table->countRows($where);
		if ($have > $max)
		{
			$orderby = 'p_uid ASC'; $joins = null; $limit = $have - $max;
			$table->deleteWhere($where, $orderby, $joins, $limit);
		}
	}
	
	#############
	### Cache ###
	#############
	private function addBot(SL_Bot $bot)
	{
		SL_Global::addPlayer($bot);
		$this->game->join($bot);
	}
	
	############
	### Tick ###
	############
	public function tick($tick)
	{
		if ($this->allowBots())
		{
			$this->spawnBots($tick);
		}
	}
	
	
	#############
	### Spawn ###
	#############
	private function spawnBots($tick)
	{
		$chances = array();
		$maxTotal = min(ceil(count($this->humans()) * 1.5), $this->tgc()->cfgMaxBots());
		$haveTotal = count($this->bots());
		if ($haveTotal < $maxTotal)
		{
			foreach ($this->scripts as $type)
			{
				$have = count(SL_Global::$TYPED_BOTS[$type]);
				$max = call_user_func(array($this->tgc(), sprintf('cfgMax%sBots', $type)));
				if ($have < $max)
				{
					$chances[$type] = $max - $have;
				}
			}
			# Any left to spawn?
			if (count($chances) > 0)
			{
				$type = GWF_Random::arrayItem(array_keys($chances));
				$bot = $this->spawnBot($type);
				$bot->afterLoad();
				$this->addBot($bot);
				$this->lastSpawn = $tick;
				$this->debugSpawn($bot);
			}
		}
	}

	private function spawnBot($type)
	{
		# User
		$user = GWF_Guest::blankUser(array(
			'user_options' => GWF_User::BOT,
			'user_name' => '#'.microtime(true),
			'user_guest_name' => $type.'#'.$this->spawncounter++,
			'user_regdate' => GWF_Time::getDate(),
			'user_saved_at' => GWF_Time::getDate(),
		));
		if (!$user->insert())
		{
			return false;
		}
		
		if (!$user->saveVars(array(
			'user_name' => '#B#'.$user->getID(),
		)))
		{
			return false;
		}
		
		# Bpt 
		if ($bot = SL_PlayerFactory::bot($user, $type))
		{
			$bot->setUser($user);
		}

		return $bot;
	}
	
	private function debugSpawn(SL_Bot $bot)
	{
		GWF_Log::logCron(sprintf('Spawned: %s', $bot->debugInfo()));
	}
	

}