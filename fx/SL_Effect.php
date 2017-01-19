<?php
abstract class SL_Effect
{
	public $finished = false;
	
	public abstract function tick(SL_Game $game, $tick);
	
	public function start() {}

	public function finish() {}
}
