<?php
/**
 * Lock a random target and try to kill it.
 * @author gizmore
 */
class TGCAI_Assassin extends SL_AIScript
{
	public function random_hp() { return 4; }
	public function random_mp() { return 2; }
	
	public function findTarget()
	{
		return $this->randomHuman();
	}

	public function tick($tick)
	{
		$target = $this->currentTarget();
		$this->bot->aiAttack($target);
		$this->bot->aiMoveNear($target);
	}
}
