<?php
class SL_ThrowAction extends SL_Action
{
	public function execute()
	{
		$throw = new SL_Throw($this->attacker(), $this->item(), $this->direction());
		$this->game()->addEffect($throw);
	}
}
