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
		$this->force = floor($player->strength() / ($item->getWeight() / 1000));
		
		$player->giveXP('ninja', 4);
	}
	
	public function finished(SL_Game $game, $tick)
	{
		return $this->force <= 0;
	}
	
	public function tick(SL_Game $game, $tick)
	{
		$floor = $game->floor($this->item->z);
		$x = $this->item->x + $this->vx;
		$y = $this->item->y + $this->vy;
		if ($this->finished($game, $tick) || (!$floor->canMove($x, $y)))
		{
			if ($target = $floor->playerAt($x, $y))
			{
				$attack = new SL_ThrowAttack($this->player, $target, $this->item, $this->force);
				$attack->execute();
			}
			$this->force = 0;
			$this->item->setPlayer(null, 'floor');
			$floor->addItem($this->item);
			$payload = GWS_Message::wr16(SL_Commands::SRV_ITEM_LAND);
		}
		else
		{
			$this->item->x = $x;
			$this->item->y = $y;
			$payload = GWS_Message::wr16(SL_Commands::SRV_ITEM_FLY);
		}
		$payload.= GWS_Message::wr32($this->player->getID());
		$payload.= GWS_Message::wr32($this->item->getID());
		$payload.= GWS_Message::wr8($this->item->x);
		$payload.= GWS_Message::wr8($this->item->y);
		$payload.= GWS_Message::wr8($this->item->z);
		$this->game->sendBinary($payload);
	
		$this->force--;
	}
}
