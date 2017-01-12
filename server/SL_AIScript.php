<?php
abstract class SL_AIScript
{
	############
	### Init ###
	############
	private static function scriptsPath() { return GWF_PATH.'module/Shadowlamb/server/ai'; }
	public static $TYPES = null;
	public static function init()
	{
		if (!self::$TYPES)
		{
			self::$TYPES = array();
			GWF_File::filewalker(self::scriptsPath(), function($entry, $path) {
				require_once $path;
				self::$TYPES[] = Common::substrUntil($entry, '.php', true);
			});
		}
		return self::$TYPES;
	}
	
	###############
	### Factory ###
	###############
	public static function factory(SL_Bot $bot)
	{
		$type = 'TGCAI_'.$bot->getType();
		return new $type($bot);
	}
	private function __construct($bot) { $this->bot = $bot; $this->difficulty = $this->rand(0.0, 0.05); }
	
	##############
	### Create ###
	##############
	public function random_gold() { return SL_Global::rand(5, 93); }
	public function random_gender() { return SL_Global::randItem(array('male', 'female')); }
	public function random_race() { return SL_Global::randItem(SL_Race::playerRaces()); }
	public function random_mode() { return SL_Global::randItem(SL_Player::$MODES); }
	public function random_color() { return SL_Global::randItem(SL_Player::$COLORS); }
	public function random_element() { return SL_Global::randItem(SL_Player::$ELEMENTS); }
	public function random_fighter() { return SL_Global::averageBase('fighter'); }
	public function random_ninja() { return SL_Global::averageBase('ninja'); }
	public function random_priest() { return SL_Global::averageBase('priest'); }
	public function random_wizard() { return SL_Global::averageBase('wizard'); }
	public function random_hp() { return 0; }
	public function random_mp() { return 0; }
	
	################
	### Abstract ###
	################
	abstract function tick($tick);
	public function findTarget() { return $this->randomPlayer(); }
	
	###########
	### Bot ###
	###########
	protected $bot;
	protected $target;
	protected $difficulty;
	
	protected function players() { return SL_Global::$PLAYERS; }
	protected function humans() { return SL_Global::$HUMANS; }
	protected function bots() { return SL_Global::$BOTS; }

	protected function randomBot() { return $this->randomTarget($this->bots()); }
	protected function randomHuman() { return $this->randomTarget($this->humans()); }
	protected function randomPlayer() { return $this->randomTarget($this->players()); }
	protected function randomTarget(array $targets) { return empty($targets) ? null : GWF_Random::arrayItem($targets); }
	
	protected function bestBot($scoreMethod, $topShuffle=3) { return $this->bestTarget($this->bots(), $scoreMethod, $topShuffle); }
	protected function bestHuman($scoreMethod, $topShuffle=3) { return $this->bestTarget($this->humans(), $scoreMethod, $topShuffle); }
	protected function bestPlayer($scoreMethod, $topShuffle=3) { return $this->bestTarget($this->players(), $scoreMethod, $topShuffle); }
	
	##############
	### Target ###
	##############
	protected function findOldTarget() { return $this->target ? SL_Global::getPlayer($this->target->getName()) : false; }
	protected function selectNewTarget() { $this->target = $this->findTarget(); return $this->target; }
	protected function currentTarget() { return $this->findOldTarget() ? $this->target : $this->selectNewTarget(); }
	protected function currentEnemyTarget()
	{
		$target = $this->currentTarget();
		return $target && $this->bot->isEnemy($target) ? $target : false;
	}
	
	protected function currentFriendlyTarget()
	{
		$target = $this->currentTarget();
		return $target && $this->bot->isFriendly($target) ? $target : false;
	}

	################
	### Commands ###
	################
	protected function moveNear($target, $instant) { $this->bot->aiMoveNear($target, $instant); }
	protected function moveTo($lat, $lng, $instant) { $this->bot->aiMoveTo($target, $instant); }
	protected function fight($target) { $this->bot->aiFight($target); }
	protected function attack($target) { $this->bot->aiAttack($target); }
	protected function brew($target, $spell) { $this->bot->aiBrew($target, $spell); }
	protected function cast($target, $spell) { $this->bot->aiCast($target, $spell); }
	
	####################
	### Kill Chances ###
	####################
	const BEST_SKILL = 0;
	const BEST_POWER = 1;
	protected function killChanceScore(SL_Player $player)
	{
		$score = 1000 / $player->health();
		$power = $this->bestKillChancePower($player);
		return $score * $power;
	}
	
	protected function killChance(SL_Player $player, $skill)
	{
		$cmp_health = $this->bot->compareTo($player, 'health');
		$cmp_power = $this->bot->compareTo($player, $skill);
		return $cmp_power / $cmp_health;
	}
	
	protected function bestKillChanceSkill(SL_Player $player)
	{
		$bestChance = $this->bestKillChance($player);
		return $bestChance[self::BEST_SKILL];
	}
	
	protected function bestKillChancePower(SL_Player $player)
	{
		$bestChance = $this->bestKillChance($player);
// 		printf("%s vs %s  –  BestKillChancePower: %s\n", $this->bot->displayName(), $player->displayName(), $bestChance[self::BEST_POWER]);
		return $bestChance[self::BEST_POWER];
	}
	
	protected function bestKillChance(SL_Player $player)
	{
		$bestSkill = 'fighter';
		$bestChance = $this->killChance($player, 'fighter');
		foreach (SL_Player::$SKILLS as $skill)
		{
			$chance = $this->killChance($player, $skill);
			if ($chance > $bestChance)
			{
				$bestChance = $chance;
				$bestSkill = $skill;
			}
		}
		return array($bestSkill, $bestChance);
	}
	
	############
	### Heal ###
	############
	public function heal($target)
	{
		if ($target)
		{
			
		}
	}
	
	public function healCommands()
	{
		
	}
	
	###############
	### Sorting ###
	###############
	private function bestTarget(array $targets, $scoreMethod, $topShuffle=3)
	{
		$dbg = 0;
		if ($dbg) echo "Best Target for {$this->bot->displayName()}\n";
		$possibleTargets = array();
		foreach ($targets as $target)
		{
			if ($target !== $this->bot)
			{
				if ($score = call_user_func(array($this, $scoreMethod), $target))
				{
					$possibleTargets[] = array($target, $score);
				}
			}
		}
		if (empty($possibleTargets))
		{
			return null;
		}
		if ($dbg) $this->printTargets($possibleTargets);
		usort($possibleTargets, function($a, $b) {
			return $a[1] - $b[1];
		});
		if ($dbg) $this->printTargets($possibleTargets);
		$possibleTargets = array_slice($possibleTargets, 0, $topShuffle);
		if ($dbg) $this->printTargets($possibleTargets);
		shuffle($possibleTargets);
		if ($dbg) $this->printTargets($possibleTargets);
		$target = array_pop($possibleTargets);
		if ($dbg) echo "Final: "; $this->printTarget($target);
		return $target[0];
	}
	
	private function printTargets(array $targets)
	{
		$i = 1;
		echo "TARGETS:\n";
		foreach ($targets as $target)
		{
			echo $i++;
			$this->printTarget($target);
		}
	}
	
	private function printTarget(array $target)
	{
		printf("%s: %.01f\n", $target[0]->getName(), $target[1]);
	}
	
	############
	### Rand ###
	############
	public function botrand($zero=0.0, $one=1.0) { return $this->rand($zero, $one, $this->difficulty); }
	public function rand($zero=0.0, $one=1.0, $difficulty=0.0)
	{
		$min = (int)(Common::clamp($zero+$difficulty, 0.0, 1.0) * 1000);
		$max = (int)(Common::clamp($one+$difficulty, $min/1000.0, 1.0) * 1000);
		$result = GWF_Random::rand($min, $max) / 1000.0;
// 		printf("Rand ($min-$max): %s\n", $result);
		return $result;
	}
	
}
