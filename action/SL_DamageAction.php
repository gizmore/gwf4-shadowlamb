<?php
class SL_DamageAction extends SL_Action
{
	private $damage;
	
	public function setDamage($damage)
	{
		$this->damage = $damage;
	}
	
	public function execute()
	{
		$this->defender()->giveHP(-$this->damage);

		# Announce dmg
		$payload = $this->payloadBegin();
		$payload.= $this->payloadDefender();
		$payload.= GWS_Message::wr16($this->damage);
		$payload.= GWS_Message::wr16($this->defender()->hp());
		$payload.= GWS_Message::wr16($this->defender()->maxHP());
		$this->game()->sendBinary($payload);

		if ($this->defender()->isDead())
		{
			$kill = new SL_KillAction($this->item(), $this->attacker(), $this->defender(), $this->direction());
			$kill->execute();
		}
	}
	
}
