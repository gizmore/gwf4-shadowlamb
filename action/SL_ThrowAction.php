<?php
class SL_ThrowAction extends SL_Action
{
	public function execute()
	{
		$this->attacker()->unequip($this->item()->getSlot());
		$this->sendThrow();
		$throw = new SL_Throw($this->attacker(), $this->item(), $this->direction());
		$this->game()->addEffect($throw);
	}
	
	public function sendThrow()
	{
		$payload = $this->payloadBegin();
		$payload.= $this->payloadAttacker();
		$payload.= $this->payloadItem();
		$payload.= $this->payloadDirection();
		$this->game()->sendBinary($payload);
	}
}
