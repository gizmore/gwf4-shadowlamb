<?php
final class SL_Map
{
	private $game, $floors;
	
	public function floors() { return $this->floors; }
	public function numFloors() { return count($this->floors); }
	public function floor($z) { return $this->floors[$z-1]; }
	
	public function __construct(SL_Game $game)
	{
		$this->game = $game;
		$this->floors = array();
	}
	
	public function addFloor(SL_Floor $floor)
	{
		$this->floors[] = $floor;
	}
	
	public function move(SL_Player $player, $x, $y)
	{
// 		return $this->floor($player->z())->tile($player->x()+$x, $player->y()+$y);
	}
		
	public function raspawnFloor(SL_Player $player)
	{
		return $this->floors[0];
	}

}
