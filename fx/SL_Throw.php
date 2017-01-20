<?php
final class SL_Throw extends SL_Effect
{
	private $game, $player, $item, $vx, $vy, $force;
	
	public function __construct(SL_Player $player, SL_Item $item, $direction)
	{
		$this->player = $player;
		$this->game = $player->game;
		$this->item = $item;
		$this->vx = SL_Player::$DIR_X[$direction];
		$this->vy = SL_Player::$DIR_Y[$direction];
		$this->item->x = $player->x;
		$this->item->y = $player->y;
		$this->item->z = $player->z;
		$this->item->setPlayer(null, 'air');
		$this->force = ceil($player->strength()+2 / ($item->getWeight() / 1000));
		
		$player->giveXP('ninja', $this->force);
	}
	
	public function start()
	{
		$this->flyMessage(SL_Commands::SRV_ITEM_FLY);
	}
	
	public function tick(SL_Game $game, $tick)
	{
		# New coords
		$floor = $game->floor($this->item->z);
		$x = $this->item->x + $this->vx;
		$y = $this->item->y + $this->vy;

		# Flying...
		if ( ($this->force <= 0) || (!$floor->canMove($x, $y)) )
		{
			if ($this->force > 0)
			{
				if ($target = $floor->playerAt($x, $y))
				{
					$this->item->x = $x;
					$this->item->y = $y;
					$attack = new SL_ThrowAttack($this->player, $target, $this->item, $this->force);
					$attack->execute();
				}
			}
			$floor->addItem($this->item);
			$this->finished = true;
			$this->flyMessage(SL_Commands::SRV_ITEM_LAND);
		}
		else
		{
			$this->item->x = $x;
			$this->item->y = $y;
			$this->flyMessage(SL_Commands::SRV_ITEM_FLY);
		}
		$this->force--;
	}
	
	private function flyMessage($command)
	{
		$payload = GWS_Message::wr16($command);
		$payload.= GWS_Message::wr32($this->player->getID());
		$payload.= GWS_Message::wr32($this->item->getID());
		$payload.= GWS_Message::wr8($this->item->x);
		$payload.= GWS_Message::wr8($this->item->y);
		$payload.= GWS_Message::wr8($this->item->z);
		$this->game->sendBinary($payload);
	}
	
}
