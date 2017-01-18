<?php
abstract class SL_Effect
{
	public abstract function tick(SL_Game $game, $tick);
	public abstract function finished(SL_Game $game, $tick);
}
