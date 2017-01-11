<?php
final class SL_MapGenerator
{
	private $game, $floor;
	private $walls;
	
	public function width() { return $this->game->width(); }
	public function height() { return $this->game->height(); }
	public function randIndex() { return $this->randX() + $this->randY() * $this->width(); }
	public function randX() { return SL_Global::rand(1, $this->width()-2); }
	public function randY() { return SL_Global::rand(1, $this->height()-2); }
	
	public function __construct(SL_Game $game)
	{
		$this->game = $game;
	}
	
	public function fill($tiles, $tile=SL_Tile::WALL)
	{
		$i = 0;
		for ($y = 0; $y < $this->height(); $y++)
		{
			for ($x = 0; $x < $this->width(); $x++)
			{
				$tiles[$i++] = $tile;
			}
		}
	}
	
	public function createFloor()
	{
		$width = $this->game->width();
		$height = $this->game->height();

		$this->floor = new SL_Floor($this->game);
		$tiles = $this->floor->tiles();
		$this->fill($tiles);
		
		$this->randomizedPrimeWalls();
		$this->cleanupGenFlags();
		
		$this->game->map()->addFloor($this->floor);
		$this->floor->debugWalls();
	}
	
	#############
	### Walls ###
	#############
	private function randomizedPrimeWalls()
	{
		$this->walls = array();
		
		$w = $this->width();
		$h = $this->height();
		$floor = $this->floor;
		
		### Border
		for ($x = 0; $x < $w; $x++) {
			$floor->setTileBit($x, 0, SL_Tile::GEN_VISITED|SL_Tile::WALL);
			$floor->setTileBit($x, $h-1, SL_Tile::GEN_VISITED|SL_Tile::WALL);
		}
		for ($y = 0; $y < $h; $y++) {
			$floor->setTileBit(0, $y, SL_Tile::GEN_VISITED|SL_Tile::WALL);
			$floor->setTileBit($w-1, $y, SL_Tile::GEN_VISITED|SL_Tile::WALL);
		}
		
		$this->pickMaze($this->randIndex());
		
		while (count($this->walls) > 0)
		{
			$this->pickMaze(array_pop($this->walls));
		}
		
	}
	
	private function cleanupGenFlags()
	{
		$i = 0;
		for ($y = 0; $y < $this->height(); $y++)
		{
			for ($x = 0; $x < $this->width(); $x++)
			{
				$this->floor->setTileBit($x, $y, SL_Tile::GEN_VISITED, false);
			}
		}
	}
	
	private function pickMaze($index)
	{
		$x = $index % $this->width();
		$y = intval($index / $this->width());
		if ($this->openVisitors($x, $y) > 1)
		{
			$this->floor->setTile($x, $y, SL_Tile::GEN_VISITED|SL_Tile::WALL);
			return;
		}

		$this->floor->setTile($x, $y, SL_Tile::GEN_VISITED|SL_Tile::STONE);
		foreach ($this->neighbours($x, $y) as $index)
		{
			if (!$this->floor->tileIndexIs($index, SL_Tile::GEN_VISITED))
			{
				$this->walls[] = $index;
			}
		}
		
		shuffle($this->walls);
	}
	
	private function neighbours($x, $y)
	{
		return array($this->floor->index($x, $y+1), $this->floor->index($x+1, $y), $this->floor->index($x, $y-1), $this->floor->index($x-1, $y));
	}
	
	private function openVisitors($x, $y)
	{
		$count = 0;
		foreach ($this->neighbours($x, $y) as $index)
		{
			if ($this->floor->tileIndexIs($index, SL_Tile::GEN_VISITED))
			{
				$count++;
			}
		}
		return $count;
	}
	
	
}