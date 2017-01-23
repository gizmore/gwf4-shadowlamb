<?php
class SL_StabAction extends SL_Action
{
	public function execute()
	{
		$a = $this->attacker();
		$d = $this->defender();
		
		if (!$d)
		{
			return $this->sendMiss();
		}
		
		$aa = $a->attack();
		$af = $a->fighter();
		$ad = $a->dexterity();

		$dd = $d->defense();
		$df = $d->fighter();
		$dd = $d->dexterity();
		
		$atk = $aa + $af + $ad;
		$def = $dd + $df + $dd;
		
		$min = Common::clamp($atk-$def, 1);
		$atk = SL_Global::rand($min, $atk);
		
		$def = SL_Global::rand(0, $def);
		
		if ($atk < $def)
		{
			$this->sendMiss();
		}
		else
		{
			$damage = Common::clamp($a->damage() + $atk - $def - $d->armor(), 1);
			$this->sendDamage($damage);
		}
	}
	
	public function sendMiss()
	{
		$action = new SL_MissAction($this->item(), $this->attacker(), $this->defender(), $this->direction());
		$action->execute();
	}
	
	public function sendDamage($damage)
	{
		$action = new SL_DamageAction($this->item(), $this->attacker(), $this->defender(), $this->direction());
		$action->setDamage($damage);
		$action->execute();
	}
	
}
