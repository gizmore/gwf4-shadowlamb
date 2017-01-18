<?php
class SL_Usable extends SL_Item
{
	public function equip(SL_Player $player)
	{
		$player->sendError(2525);
	}
}

