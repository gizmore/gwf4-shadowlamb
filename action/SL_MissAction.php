<?php
class SL_MissAction extends SL_Action
{
	public function execute()
	{
		# Announce Miss
		$payload = $this->payloadBegin();
		$this->game()->sendBinary($payload);
	}
	
}
