<?php
require_once 'SL_Player.php';

final class SL_Bot extends SL_Player
{
	private $script;
	private $command = null;
	
	public function getClassName() { return __CLASS__; }
	
	###############
	### Getters ###
	###############
	public function handler() { return $this->game->handler(); } 
	public function getID() { return $this->getVar('p_uid'); }
	public function getType() { return $this->getVar('p_type'); }
	public function target() { return $this->script->target(); }
	public function getScript() { return $this->script; }
	public function lastCommand() { return $this->command; }
	public function killXP(SL_Player $killer) { return 1 + $this->playerLevel(); }
	
	############
	### Stub ###
	############
	public function sendText($messageText) { printf("%s << %s\n", $this->displayName(), $messageText); }
	public function sendBinary($messageData) { printf("%s << %s\n", $this->displayName(), 'BINARY'); }
	
	############
	### Kill ###
	############
	public function killedBy(SL_Player $killer)
	{
		if ($this->deletePlayer())
		{
			$payload = json_encode(array(
				'killer' => $killer->getName(),
				'victim' => $this->getName(),
			));
			$this->forNearMe(function(SL_Player $player, $payload) {
				$player->sendCommand('SL_BOTKILL', $payload);
			}, $payload);
		}
	}
	
	public function deletePlayer()
	{
		if (parent::deletePlayer())
		{
			return $this->getUser()->delete();
		}
		return false;
	}
	
	##############
	### Events ###
	##############
	public function afterLoad()
	{
		$this->script = SL_AIScript::factory($this);
		parent::afterLoad();
	}
	
	public function tick($tick)
	{
		parent::tick($tick);
		$this->command = null;
		$this->script->tick($tick);
		if ($this->command)
		{
			list($command, $payload) = $this->command;
			$this->tickExecute($command, $payload);
		}
	}
	
	private function tickExecute($command, $payload)
	{
		printf("%s >> %s:%s\n", $this->displayName(), $command, $payload);
		$method = array($this->handler(), 'cmd_'.$command);
		call_user_func($method, $this->getUser(), $payload, GWS_Commands::DEFAULT_MID);
	}
	
	###################
	### Move Helper ###
	###################
	public function aiMoveNear($player)
	{
		$dirs = array('N', 'E', 'S', 'W');
		if ($player)
		{
			$this->aiMove($dirs[array_rand($dirs)]);
		}
	}
	
	###############
	### Command ###
	###############
	public function aiJSONCommand($command, array $object)
	{
		return $this->aiCommand($command, json_encode($object));
	}
	
	public function aiCommand($command, $payload)
	{
		if (!$this->command)
		{
			$this->command = array($command, $payload);
		}
	}
	
	#################
	### Commands ####
	#################
	public function aiMove($direction)
	{
		$this->move($direction);
	}
	
	public function aiFight($player, $command='tgcFight')
	{
		if ($player)
		{
// 			$this->aiCommand($command, $player->getName());
		}
	}
	
	public function aiAttack($player)
	{
// 		$this->aiFight($player, 'tgcAttack');
	}
	
	public function aiCast($player, $spell)
	{
	
	}
	
	public function aiBrew($player, $spell)
	{
	
	}
	

}
