<?php
abstract class SL_Action
{
	public abstract function execute();
	
	private $item;
	private $attacker;
	private $defender;
	private $direction;
	
	/**
	 * @return SL_Game
	 */
	public function game()
	{
		return $this->attacker->game;
	}
	
	/**
	 * @return SL_Player
	 */
	public function attacker()
	{
		return $this->attacker;
	}
	
	/**
	 * @return SL_Player
	 */
	public function defender()
	{
		return $this->defender;
	}

	/**
	 * @return SL_Item
	 */
	public function item()
	{
		return $this->item;
	}
	
	public function direction()
	{
		return $this->direction;
	}
	
	public function actionId()
	{
		$class = get_class($this);
		return SL_ItemFactory::actionToID(substr($class, 3, strlen($class)-9));
	}
	
	public function __construct($item, $attacker, $defender, $direction)
	{
		$this->item = $item;
		$this->attacker = $attacker;
		$this->defender = $defender;
		$this->direction = $direction;
	}
	
	public function payload()
	{
		return
			GWS_Message::wr16(SL_Commands::SRV_ACTION).
			GWS_Message::wr8($this->actionId()).
			GWS_Message::wr32($this->attacker()->getID()).
			GWS_Message::wr32($this->defender ? $this->defender->getID() : 0).
			GWS_Message::wr32($this->item ? $this->item->getID() : 0).
			GWS_Message::wr8(ord($this->direction));
		}
	
}
