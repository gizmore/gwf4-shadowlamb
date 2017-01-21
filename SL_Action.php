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
	
	public function __construct($item, $attacker, $defender, $direction)
	{
		$this->item = $item;
		$this->attacker = $attacker;
		$this->defender = $defender;
		$this->direction = $direction;
	}
}
