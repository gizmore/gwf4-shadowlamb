<?php
require_once 'SL_AI.php';
require_once 'SL_Global.php';

final class SL_Commands extends GWS_Commands
{
	private $sl;
	
	const SRV_POS = 0x2001;
	const SRV_OWN = 0x2002;
	const SRV_PLAYER = 0x2003;
	const SRV_MAP = 0x2004;
	const SRV_LVLUP = 0x2005;
	const SRV_OUCH = 0x2010;
	const SRV_ITEM_PICKUP = 0x2020;
	const SRV_ITEM_DROP = 0x2021;
	const SRV_ITEM_THROW = 0x2022;
	const SRV_ITEM_FLY = 0x2023;
	const SRV_ITEM_LAND = 0x2025;
	const SRV_ITEM_INFO = 0x2024;
	const SRV_ITEM_EQUIPPED = 0x2026;
	const SRV_ITEM_UNEQUIPPED = 0x2027;
	const SRV_ITEM_DEPOSIT = 0x2028;
	const SRV_ITEM_WITHDRAW = 0x2029;
	const SRV_ITEM_ACTIONS = 0x2030;
	
	const CLT_PLAYER_INFO = 0x2001;
	const CLT_MOV = 0x2002;
	const CLT_ITEM_INFO = 0x2003;
	const CLT_PICKUP = 0x2010;
	const CLT_DROP = 0x2011;
	const CLT_THROW = 0x2012;
	const CLT_EQUIP = 0x2014;
	const CLT_UNEQUIP = 0x2015;
	const CLT_DEPOSIT = 0x2016;
	const CLT_WITHDRAW = 0x2017;
	const CLT_ATTACK = 0x2023;
	const CLT_RUNE = 0x2030;
	const CLT_CAST = 0x2031;
	
	############
	### Init ###
	############
	public function init()
	{
		GWF_Log::logCron('SL_Commands::init()');
		$this->sl = Module_Shadowlamb::instance();
		SL_ItemFactory::init();
		SL_Global::init(31337, $this);
		SL_Spell::init();
		SL_Global::$GAMES->createGame();
		$this->timer();
	}
	
	#############
	### Timer ###
	#############
	public function timer()
	{
		SL_Global::tick();
	}
	
	##################
	### Disconnect ###
	##################
	public function disconnect(GWF_User $user)
	{
		parent::disconnect($user);
		if ($player = SL_Global::getPlayer($user->getID()))
		{
			if ($player->game)
			{
				$player->game->part($player);
			}
			SL_Global::removePlayer($player);
		}
	}
	
	##############
	### Helper ###
	##############
	/**
	 * @param GWS_Message $msg
	 * @return SL_Player
	 */
	private static function player(GWS_Message $msg)
	{
		return SL_Global::getOrCreatePlayer($msg->user());
	}
	
	
	#####################
	### Slow commands ###
	#####################
	public function xcmd_sl_reset(GWS_Message $msg)
	{
		$msg->replyError(0x0008);
	}
	
	public function cmd_sl_gamelist(GWS_Message $msg)
	{
		$payload = array();
		foreach (SL_Global::$GAMES->allGames() as $game)
		{
			$payload[] = $game->gamelistDTO();
		}
		$msg->replyText('SL_GAMELIST', json_encode($payload));
	}
	
	public function cmd_sl_newgame(GWS_Message $msg)
	{
		$game = SL_Global::$GAMES->createGame();
		$msg->replyText('SL_NEWGAME', json_encode($game->gamelistDTO()));
	}
	
	public function cmd_sl_joingame(GWS_Message $msg)
	{
		$player = self::player($msg);
		if (!($game = SL_Global::gameByName($msg->readPayload())))
		{
			return $msg->replyError(self::ERR_UNKNOWN_GAME);
		}
	
		if ($player->game !== $game)
		{
			if (($player->game) && ($player->game !== $game))
			{
				return $msg->replyError(self::ERR_ALREADY_IN_GAME);
			}
			if (true !== ($error = $game->canJoin($player)))
			{
				return $msg->replyErrorMessage(self::ERR_JOIN_GAME, $error);
			}
			$game->join($player);
		}
		$msg->replyText('SL_JOINGAME', json_encode($game->gamelistDTO())); # Reply sync
		$player->requestFirstMap(); # Get first map square
		$player->sendBinary($msg->write16(self::SRV_OWN).$player->payloadOwn()); # Get own stats
		$game->sendBinary($player->payloadPos()); # Announce pos to all
	}
	
	public function cmd_sl_partgame(GWS_Message $msg)
	{
	
	}
	
	
	#######################
	### Binary Commands ###
	#######################
	/**
	 * Get player info
	 */
	public function xcmd_2001(GWS_Message $msg)
	{
		if (!($other = SL_Global::getPlayer($msg->read32())))
		{
			$msg->replyError(self::ERR_UNKNOWN_PLAYER);
		}
		else
		{
			$payload = GWS_Message::wr16(self::SRV_PLAYER);
			$payload.= $other->payloadOther();
			self::player($msg)->sendBinary($payload);
		}
	}
	
	/**
	 * Move command
	 */
	public function xcmd_2002(GWS_Message $msg)
	{
		$player = self::player($msg);
		$direction = chr($msg->read8());
		if (!strpos(' NSEW', $direction))
		{
			return $msg->replyError(self::ERR_UNKNOWN_DIRECTION);
		}
		return $player->move($direction);
	}
	
	/**
	 * Get item info
	 */
	public function xcmd_2003(GWS_Message $msg)
	{
		$player = self::player($msg);
		if (!($item = SL_Item::getCached($msg->read32())))
		{
			return $msg->replyError(self::ERR_UNKNOWN_ITEM);
		}
		$msg->replyBinary(self::SRV_ITEM_INFO, $item->payload());
	}
	
	/**
	 * Pickup item.
	 */
	public function xcmd_2010(GWS_Message $msg)
	{
		$player = self::player($msg);
		$floor = $player->floor;
		if (!($item = SL_Item::getCached($msg->read32())))
		{
			return $msg->replyError(self::ERR_UNKNOWN_ITEM);
		}
		if ($player->hand())
		{
			return $msg->replyError(self::ERR_ALREADY_HAND);
		}
		if ( ($item->x !== $player->x) || ($item->y !== $player->y) || (!$item->onFloor()) )
		{
			return $msg->replyError(self::ERR_ITEM_NOT_NEAR);
		}
		
		$floor->removeItem($item);
		$player->handItem($item);

		# Send success
		$payload = $msg->write16(self::SRV_ITEM_PICKUP);
		$payload.= $msg->write32($player->getID());
		$payload.= $msg->write32($item->getID());
		$player->game->sendBinary($payload);
	}
	
	/**
	 * Drop item
	 */
	public function xcmd_2011(GWS_Message $msg)
	{
		$player = self::player($msg);
		$floor = $player->floor;
		if (!$player->hand())
		{
			return $msg->replyError(self::ERR_NOT_IN_HAND, 'NONE');
		}
		if ($player->hand()->getID() != $msg->read32())
		{
			return $msg->replyErrorMessage(self::ERR_NOT_IN_HAND, 'MISSID');
		}
		
		$item = $player->removeHand();
		$floor->addItem($item);
		
		# Send success
		$payload = $msg->write16(self::SRV_ITEM_DROP);
		$payload.= $msg->write32($player->getID());
		$payload.= $msg->write32($item->getID());
		$payload.= $msg->write8($item->x);
		$payload.= $msg->write8($item->y);
		$payload.= $msg->write8($item->z);
		$player->game->sendBinary($payload);
	}
	
	/**
	 * Throw item
	 */
	public function xcmd_2012(GWS_Message $msg)
	{
		$player = self::player($msg);
		$floor = $player->floor;
		if (!($item = $player->hand()))
		{
			return $msg->replyError(self::ERR_NOT_IN_HAND);
		}
		if ($item->getID() != $msg->read32())
		{
			return $msg->replyError(self::ERR_NOT_IN_HAND);
		}
		$direction = chr($msg->read8());
		if (!strpos(' NSEW', $direction))
		{
			return $msg->replyError(self::ERR_UNKNOWN_DIRECTION);
		}

		# Do it
		$player->throw($direction);
		
		# Send success
		$payload = $msg->write16(self::SRV_ITEM_THROW);
		$payload.= $msg->write32($player->getID());
		$payload.= $msg->write32($item->getID());
		$payload.= $msg->write8($item->x);
		$payload.= $msg->write8($item->y);
		$payload.= $msg->write8($item->z);
		$player->game->sendBinary($payload);
	}
	
	/**
	 * Equip
	 */
	public function xcmd_2014(GWS_Message $msg)
	{
		$player = self::player($msg);

		# Check valid slot
		$slotint = $msg->read8();
		if (!SL_Item::validEquipmentSlotInt($slotint))
		{
			return $msg->replyError(self::ERR_NO_SLOT);
		}
		
		# Check Hand sync
		$handid = $msg->read32();
		$hand = $player->hand();
		if (!$hand)
		{
			return;# $msg->replyErrorMessage(self::ERR_HAND_SYNC, 'NONE');
		}
		if ($hand->getID() != $handid)
		{
			return $msg->replyErrorMessage(self::ERR_HAND_SYNC, 'MISSID');
		}
		
		# Check slot fit
		$slot = SL_Item::slotEnum($slotint);
		if (!$player->slotFit($hand, $slot))
		{
			return;# $msg->replyError(self::ERR_WRONG_SLOT);
		}
		
		# Exchange
		$new = $player->equip($slot);
		$old = $player->hand();
		
		# Reply
		$payload = $msg->write8($slotint);
		$payload.= $msg->write32($new->getID());
		$payload.= $msg->write32($old ? $old->getID() : 0);
		$msg->replyBinary(self::SRV_ITEM_EQUIPPED, $payload);
	}
	
	/**
	 * Unequip
	 */
	public function xcmd_2015(GWS_Message $msg)
	{
		$player = self::player($msg);
	
		# Check valid slot
		$slotint = $msg->read8();
		if (!SL_Item::validEquipmentSlotInt($slotint))
		{
			return $msg->replyError(self::ERR_NO_SLOT);
		}
	
		# Check hand empty
		if ($player->hand())
		{
			return $msg->replyErrorMessage(self::ERR_HAND_SYNC, 'WRONG');
		}
	
		# Unequip
		$slot = SL_Item::slotEnum($slotint);
		if ($item = $player->unequip($slot))
		{
			# Reply
			$payload = $msg->write8($slotint);
			$payload.= $msg->write32($item->getID());
			$msg->replyBinary(self::SRV_ITEM_UNEQUIPPED, $payload);
		}
	}
	
	/**
	 * Deposit to inventory
	 */
	public function xcmd_2016(GWS_Message $msg)
	{
		$player = self::player($msg);
		$index = $msg->read8(); # unused atm
		
		# Check Hand sync
		$handid = $msg->read32();
		$hand = $player->hand();
		if (!$hand)
		{
			return $msg->replyErrorMessage(self::ERR_HAND_SYNC, 'NONE');
		}
		if ($hand->getID() != $handid)
		{
			return $msg->replyErrorMessage(self::ERR_HAND_SYNC, 'MISSID');
		}
		
		# Check inventory load
		if (count($player->inventory()) >= $this->sl->cfgMaxInvSlots())
		{
			return $msg->replyError(self::ERR_INV_FULL);
		}
		
		# Give
		if (!$player->giveItem($hand))
		{
			return $msg->replyErrorMesssage(self::ERR_DB);
		}
		$player->handItem();

		# Reply
		$payload = $msg->write8($index);
		$payload.= $msg->write32($hand->getID());
		$msg->replyBinary(self::SRV_ITEM_DEPOSIT, $payload);
	}
	
	/**
	 * Withdraw from inventory
	 */
	public function xcmd_2017(GWS_Message $msg)
	{
		$player = self::player($msg);
		$index = $msg->read8(); # unused atm
		
		# Check Hand sync
		$handid = $msg->read32();
		$hand = $player->hand();
		if ($hand)
		{
			if ($hand->getID() != $handid)
			{
				return $msg->replyErrorMessage(self::ERR_HAND_SYNC, 'MISSID');
			}
		}
		elseif ($handid != 0)
		{
			return $msg->replyErrorMessage(self::ERR_HAND_SYNC, 'SYNC');
		}
		
		# Chck item exist in inv
		$itemid = $msg->read32();
		if (!($item = $player->inventoryItem($itemid)))
		{
			return $msg->replyError(self::ERR_ITEM_NOT_NEAR);
		}
		
		if ($hand)
		{
			$player->giveItem($hand);
		}
		
		$player->handItem($item);
		
		# Reply
		$payload = $msg->write8($index); # Inv index withdrawn
		$payload.= $msg->write32($item->getID()); # Now in hand
		$payload.= $msg->write32($hand ? $hand->getID() : 0); # Now the same index in inv
		$msg->replyBinary(self::SRV_ITEM_WITHDRAW, $payload);
	}
	
// 	/**
// 	 * Item actions
// 	 */
// 	public function xcmd_2025(GWS_Message $msg)
// 	{
// 		$player = self::player($msg);
		
// 		# Get item
// 		if (!($item = SL_Item::getCached($msg->read32())))
// 		{
// 			return $msg->replyError(self::ERR_UNKNOWN_ITEM);
// 		}
// 		if ($item->getUserID() != $player->getID())
// 		{
// 			return $msg->replyErrorMessage(self::ERR_UNKNOWN_ITEM, 'OWN');
// 		}
		
// 		# Reply
// 		$payload = $msg->write32($item->getID());
// 		$payload.= $msg->writeString($item->actionName(0));
// 		$payload.= $msg->writeString($item->actionName(1));
// 		$payload.= $msg->writeString($item->actionName(2));
// 		$msg->replyBinary(self::SRV_ITEM_ACTIONS, $payload);
// 	}
	
	/**
	 * Attack
	 */
	public function xcmd_2023(GWS_Message $msg) { $this->CMD_attack($msg, 'shield'); }
	public function xcmd_2024(GWS_Message $msg) { $this->CMD_attack($msg, 'weapon'); }
	public function CMD_attack(GWS_Message $msg, $slot)
	{
		$player = self::player($msg);

		# Check direction
		$direction = chr($msg->read8());
		if (!strpos(' NSEW', $direction))
		{
			return $msg->replyError(self::ERR_UNKNOWN_DIRECTION);
		}
		
		# Check defender
		$x = $player->xFacing($direction);
		$y = $player->yFacing($direction);
		$defender = $player->floor->playerAt($x, $y);
		
		# Check weapon
		$weaponId = $msg->read32();
		$weapon = $player->weapon($slot);
		if ( (!$weapon) || ($weapon->getID() != $weaponId) )
		{
			return $msg->replyError(self::ERR_HAND_SYNC);
		}
		
		# Check type
		if (!($classname = SL_ItemFactory::actionClassname($msg->read8())))
		{
			return $msg->replyError(self::ERR_UNKNOWN_ACTION);
		}
		
		# Exec
		$action = new $classname($player->game, $weapon, $player, $defender, $direction);
		$action->execute();
	}
	
	/**
	 * Rune
	 */
	public function xcmd_2030(GWS_Message $msg)
	{
		$player = self::player($msg);
	}
	
	/**
	 * Cast
	 */
	public function xcmd_2031(GWS_Message $msg)
	{
		$player = self::player($msg);
	}

	const ERR_UNKNOWN_GAME = 0x2001;
	const ERR_ALREADY_IN_GAME = 0x2002;
	const ERR_UNKNOWN_PLAYER = 0x2003;
	const ERR_UNKNOWN_DIRECTION = 0x2004;
	const ERR_UNKNOWN_ITEM = 0x2005;
	const ERR_ALREADY_HAND = 0x2006;
	const ERR_ITEM_NOT_NEAR = 0x2007;
	const ERR_NOT_IN_HAND = 0x2008;
	const ERR_NO_SLOT = 0x2009;
	const ERR_SLOT_NOT_FIT= 0x200A;
	const ERR_HAND_SYNC = 0x200B;
	const ERR_WRONG_SLOT = 0x200C;
	const ERR_ATTACK_TYPE = 0x200D;
	const ERR_INV_FULL = 0x200E;
	const ERR_DB = 0x200F;
	const ERR_WAY_BLOCKED = 0x2010;
	const ERR_UNKNOWN_ACTION = 0x2011;
}
