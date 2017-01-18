<?php
class SL_ThrowAttack extends SL_Attack
{
	protected $item, $force;
	
	public function __construct(SL_Player $attacker, SL_Player $defender, SL_Item $item, $force)
	{
		$this->attacker = $attacker;
		$this->defender = $defender;
		$this->item = $item;
		$this->force = $force;
	}

	public function execute()
	{
		
	}
	

}
