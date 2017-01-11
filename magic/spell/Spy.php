<?php
class Spy extends SL_Spell
{
	public function getSpellName()
	{
		return 'spy';
	}

	public function getCode()
	{
		return sprintf('TARGET.update(%s)', json_encode($this->target->ownPlayerDTO()));
	}

}
