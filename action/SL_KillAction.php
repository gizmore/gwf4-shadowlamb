<?php
class SL_KillAction extends SL_Action
{
	public function execute()
	{
		# Announce
		$this->sendKill();
		$payload = $this->payloadBegin();
		$payload.= $this->payloadDefender();
		$this->game()->sendBinary($payload);

		$this->kill();
		
		$this->game()->part($this->defender());
	}
	
	public function sendKill()
	{
		$payload = $this->payloadBegin();
		$payload.= $this->payloadDefender();
		$this->game()->sendBinary($payload);
	}

	public function kill()
	{
		$this->defender()->deletePlayer();
	}
	
}
