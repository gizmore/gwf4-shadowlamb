<?php
class SL_KickAttack extends SL_Attack
{
	public function __construct(SL_Player $attacker, SL_Player $defender)
	{
		$this->attacker = $attacker;
		$this->defender = $defender;
	}

	public function execute()
	{
		
	}
	

}
