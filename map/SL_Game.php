<?php
final class SL_Game
{
	const STATIC = 1;
	const DYNAMIC = 2;
	const TOURNAMENT = 3;
	
	private $name;
	private $owner;
	private $map;
	private $config;
	private $ai;
	private $handler;
	private $players, $bots, $humans;
	private $items, $effects;
	
	public function map() { return $this->map; }
	public function floor($z) { return $this->map->floor($z); }
	public function handler() { return $this->handler; }
	public function bots() { return $this->bots; }
	public function humans() { return $this->humans; }
	public function config() { return $this->config; }
	public function players() { return $this->players; }
	public function type() { return $this->config['type']; }
	public function width() { return $this->config['width']; }
	public function height() { return $this->config['height']; }
	public function numPlayers() { return count($this->players); }
	public function maxPlayers() { return $this->config['max_players']; }
	public function open() { return $this->numPlayers() < $this->maxPlayers(); }
	public function numFloors() { return $this->map()->numFloors(); }
	public function numTiles() { return $this->width() * $this->height(); }
	public function setName($name) { $this->name = $name; }
	public function name() { return $this->name; }
	
	public function defaultConfig()
	{
		return array(
			'type' => self::STATIC,
			'width' => 16, 'height' => 16,
			'max_players' => 32,
		);
	}
	
	public function __construct(array $config, $handler)
	{
		$this->name = '';
		$this->handler = $handler;
		$this->config = array_merge($this->defaultConfig(), $config);
		$this->players = array();
		$this->bots = array();
		$this->humans = array();
		$this->items = array();
		$this->effects = array();
		$this->map = new SL_Map($this);
		$this->ai = new SL_AI();
	}
	
	public function init()
	{
		$this->ai->init($this);
	}

	public function canJoin(SL_Player $player)
	{
		return ($this->numPlayers() < $this->maxPlayers()) && (!isset($this->humans[$player->getID()]));
	}
	
	public function join(SL_Player $player)
	{
		$pid = $player->getID();
		$this->players[$pid] = $player;
		if ($player->isBot())
		{
			$this->bots[$pid] = $player;
		}
		else
		{
			$this->humans[$pid] = $player;
		}
		$player->setGame($this);
		$this->spawn($player);
		return $this;
	}
	
	public function part(SL_Player $player)
	{
		$player->setGame(null);
		$pid = $player->getID();
		unset($this->players[$pid]);
		unset($this->humans[$pid]);
		unset($this->bots[$pid]);
	}
	
	public function spawn(SL_Player $player)
	{
		$floor = $this->map->raspawnFloor($player);
		$index = $floor->respawnIndex($player);
		$player->setToFloorIndex($floor, $index);
	}
	
	public function createFloor()
	{
		$gen = new SL_MapGenerator($this);
		return $gen->createFloor();
	}
	
	###############
	### Effects ###
	###############
	public function addEffect(SL_Effect $effect)
	{
		$this->effects[] = $effect;
	}

	############
	### Tick ###
	############
	public function tick($tick)
	{
		$this->ai->tick($tick);
		
		foreach ($this->effects as $i => $effect)
		{
			$effect->tick($this, $tick);
			if ($effect->finished($this, $tick))
			{
				unset($this->effects[$i]);
			}
		}
		
		foreach ($this->players as $player)
		{
			$player->tick($tick);
		}
	}
	
	###########
	### DTO ###
	###########
	public function gamelistDTO()
	{
		return array(
			'name' => $this->name,	
			'config' => $this->config,
			'players' => count($this->players),
		);
	}
	
	##########
	### WS ###
	##########
	public function sendText($payload) { foreach ($this->players as $player) { $player->sendText($payload); } }
	public function sendBinary($payload) { foreach ($this->players as $player) { $player->sendBinary($payload); } }
	
	#################
	### Serialize ###
	#################
	public function mapDir() { return GWF_PATH.'dbimg/shadowlamb/'; }
	public function mapFile() { return $this->mapDir().'map.txt'; }
	
	public function saveMap()
	{
		if (!GWF_File::createDir($this->mapDir()))
		{
			return 'ERR_CREATE_DIR';
		}
	}
	
	public function deserialize()
	{
		
	}
}