<?php
class SL_ThrowAttackAction extends SL_Action
{
	private $force;
	
	public function setForce($force)
	{
		$this->force = $force;
	}
	
	
	public function execute()
	{
		$attack = new SL_DamageAction($this->item(), $this->attacker(), $this->defender(), $this->direction());
		$attack->setDamage($this->force);
		$attack->execute();
	}
}
