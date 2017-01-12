<?php
require_once 'SL_Tile.php';
require_once 'SL_Floor.php';
require_once 'SL_Map.php';
require_once 'SL_Game.php';
require_once 'gen/SL_MapGenerator.php';

final class SL_Games
{
	private $games = array();
	
	public function allGames()
	{
		return $this->games;
	}

	public function openGames()
	{
		$games = array();
		foreach ($this->games as $game)
		{
			if ($game->open())
			{
				$games[] = $game;
			}
		}
		return $games;
	}
	
	public function addGame(SL_Game $game)
	{
		$game->setName(sprintf('Game %d', count($this->games)+1));
		$this->games[$game->name()] = $game;
	}
	
	public function createGame($config=array())
	{
		$game = new SL_Game($config);
		$game->createFloor();
		$this->addGame($game);
		return $game;
	}
	
	public function getGame($name)
	{
		return isset($this->games[$name]) ? $this->games[$name] : null;
	}
	
	
}