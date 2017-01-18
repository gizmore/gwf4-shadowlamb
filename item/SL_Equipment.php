<?php
class SL_Equipment extends SL_Item
{
	public function equip(SL_Player $player)
	{
		$equip = $player->equipment();
		$slot = $this->equipmentSlot();
		$player->unequip($slot);
		$player->equipped($this);
		$this->saveVars(array(
			'i_slot' 
		));
	}
}

