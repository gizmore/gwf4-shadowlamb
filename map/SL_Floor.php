<?php
final class SL_Floor
{
	private $game, $width, $number, $tiles, $items;
	
	public function tiles() { return $this->tiles; }
	public function height() { return $this->game->height(); }
	public function x($index) { return $index % $this->width; }
	public function y($index) { return intval($index / $this->width); }
	public function z() { return $this->number; }
	public function index($x, $y) { return $x + $y * $this->width; }
	public function tile($x, $y) { return $this->tiles[$this->index($x, $y)]; }
	public function tileIndex($index) { return $this->tiles[$index]; }
	public function setTile($x, $y, $tile) { $this->tiles[$this->index($x, $y)] = $tile; }
	public function setTileIndex($index, $tile) { $this->tiles[$index] = $tile; }
	public function setTileBit($x, $y, $bit, $enabled=true) { $old = $this->tile($x, $y); $this->setTile($x, $y, $enabled ? $old | $bit : $old & ~$bit); }
	
	public function tileIs($x, $y, $bit) { return ($this->tile($x, $y) & $bit) === $bit; }
	public function tileIndexIs($index, $bit) { return ($this->tiles[$index] & $bit) === $bit; }
	
	public function respawnIndex(SL_Player $player) { return $this->randNonWallIndex(); }
	public function randNonWallIndex() { do { $index = $this->randIndex(); } while ($this->tileIndexIs($index, SL_Tile::WALL)); return $index; }
	public function randIndex() { return $this->randX() + $this->randY() * $this->width; }
	public function randX() { return SL_Global::rand(1, $this->game->width()-2); }
	public function randY() { return SL_Global::rand(1, $this->game->height()-2); }
	
	public function __construct(SL_Game $game)
	{
		$this->game = $game;
		$this->width = $game->width();
		$this->number = $game->numFloors() + 1;
		$this->tiles = new SplFixedArray($game->numTiles());
		$this->items = array();
	}
	
	public function canMove($x, $y)
	{
		return ((!$this->tileIs($x, $y, SL_Tile::WALL)) &&
				(!$this->playerAt($x, $y)));
	}

	#############
	### Items ###
	#############
	public function getItem($itemId) { return isset($this->items[$itemId]) ? $this->items[$itemId] : null; }
	public function removeItem(SL_Item $item) { unset($this->items[$item->getID()]); }
	public function addItem(SL_Item $item) { $this->addItemAt($item, $item->x, $item->y); }
	public function addItemAtRandom(SL_Item $item) { $index = $this->randNonWallIndex(); $this->addItemAt($item, $this->x($index), $this->y($index)); }
	public function addItemAt(SL_Item $item, $x, $y)
	{
		$item->x = $x;
		$item->y = $y;
		$item->z = $this->number;
		$this->items[$item->getID()] = $item;
	}
	
	
	#############
	### Debug ###
	#############
	public function debugWalls()
	{
		$i = 0;
		for ($y = 0; $y < $this->game->height(); $y++)
		{
			for ($x = 0; $x < $this->width; $x++)
			{
				$look = SL_Tile::canLook($this->tiles[$i++]);
				echo $look ? ' ' : '#';
			}
			echo "\n";
		}
	}
	
	###############
	### Message ###
	###############
	public function payloadMap($x1, $y1, $x2, $y2)
	{
		$w = $this->width; $h = $this->height();
		$x1 = Common::clamp($x1, 0, $w-1); $x2 = Common::clamp($x2, 0, $w-1);
		$y1 = Common::clamp($y1, 0, $h-1); $y2 = Common::clamp($y2, 0, $h-1);
		$payload = GWS_Message::wr16(SL_Commands::SRV_MAP);
		$payload.= GWS_Message::wr8($x1).GWS_Message::wr8($y1);
		$payload.= GWS_Message::wr8($x2).GWS_Message::wr8($y2);
		$payload.= GWS_Message::wr8($this->number);
		for ($y = $y1; $y <= $y2; $y++)
		{
			for ($x = $x1; $x <= $x2; $x++)
			{
				$payload .= GWS_Message::wr8($this->tile($x, $y));
			}
		}
		
		$payload .= $this->payloadItems($x1, $y1, $x2, $y2);
		
		return $payload;
	}
	
	private function itemsInRect($x1, $y1, $x2, $y2)
	{
		$result = array();
		foreach ($this->items as $item)
		{
			if ( ($item->x >= $x1) && ($item->x <= $x2) && ($item->y >= $y1) && ($item->y <= $y2) )
			{
				$result[] = $item;
			}
		}
		return $result;
	}
			
	private function payloadItems($x1, $y1, $x2, $y2)
	{
		$items = $this->itemsInRect($x1, $y1, $x2, $y2);
		$payload = GWS_Message::wr16(count($items));
		foreach ($items as $item)
		{
			$payload .= $item->payload();
		}
		return $payload;
	}
	
	public function playerAt($x, $y)
	{
		foreach ($this->game->players() as $player)
		{
			if (($player->x === $x) && ($player->y === $y))
			{
				return $player;
			}
		}
		return null;
	}
	
}
