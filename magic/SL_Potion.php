<?php
abstract class SL_Potion extends SL_Spell
{
	public function cast() { $this->player->sendError('ERR_NO_CAST'); }
	
	public function canTargetSelf() { return true; }
	public function canTargetOther() { return true; }
	public function waterCost() { return $this->level; }
	public function playerLevel() { return $this->player->priest() + ceil($this->player->wizard()/4) + 1; }
	
	public function brew()
	{
		if ($this->player->water() >= $this->waterCost())
		{
			$this->player->giveWater($this->waterCost());
			$this->spell();
		}
		else
		{
			$this->player->sendError('ERR_NO_WATER');
		}
	}
	
#####

	public function executeSpell()
	{
		$this->executeDefaultBrew();
	}
	
}
