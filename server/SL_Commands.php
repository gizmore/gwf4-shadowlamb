<?php
require_once 'SL_AI.php';
require_once 'SL_Global.php';

final class SL_Commands extends GWS_Commands
{
	const ERR_UNKNOWN_GAME = 0x2001;
	const ERR_ALREADY_IN_GAME = 0x2002;
	const ERR_UNKNOWN_PLAYER = 0x2003;
	const ERR_UNKNOWN_DIRECTION = 0x2004;
	const ERR_WAY_BLOCKED = 0x2010;

	const SRV_POS = 0x2001;
	const SRV_OWN = 0x2002;
	const SRV_PLAYER = 0x2003;
	const SRV_MAP = 0x2004;
	
	const CLT_MOV = 0x2002;
	
	private $sl, $acc, $ai;
	
	############
	### Init ###
	############
	public function init()
	{
		GWF_Log::logCron('SL_Commands::init()');
		$this->sl = Module_Tamagochi::instance();
// 		$this->acc = GWF_Module::loadModuleDB('Account', true, true);
// 		$this->changeNick = $this->modAccount->getMethod('ChangeGuestNickname');
		SL_Global::init(31337);
		SL_Spell::init();
		$this->ai = new SL_AI();
		$this->ai->init($this);
		$this->timer();
	}
	
	#############
	### Timer ###
	#############
	public function timer()
	{
		$this->ai->tick(SL_Global::tick());
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
	private static function player(GWS_Message $msg)
	{
		return SL_Global::getOrCreatePlayer($msg->user());
	}
	
	################
	### Commands ###
	################
	public function xcmd_2001(GWS_Message $msg)
	{
		if (!($other = SL_Global::getPlayer($msg->read32())))
		{
			$msg->replyError(self::ERR_UNKNOWN_PLAYER);
		}
		else
		{
			self::player($msg)->sendBinary($other->payloadPlayer());
		}
	}
	
	public function xcmd_2002(GWS_Message $msg)
	{
		$player = self::player($msg);
		$direction = chr($msg->read8());
		if (!strpos(' NSEW', $direction))
		{
			return $player->sendError(self::ERR_UNKNOWN_DIRECTION);
		}
		return $player->move($direction);
	}
	
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
		if ($player->game)
		{
			return $msg->replyError(self::ERR_ALREADY_IN_GAME);
		}
		if (true !== ($error = $game->canJoin($player)))
		{
			return $msg->replyErrorMessage(self::ERR_JOIN_GAME, $error);
		}
		
		$game->join($player);
		$msg->replyText('SL_JOINGAME', json_encode($game->gamelistDTO())); # Reply sync
		$player->sendBinary($player->payloadOwn()); # Get own stats
		$player->requestFirstMap(); # Get first map square
		$game->sendBinary($player->payloadPos()); # Announce pos to all
	}
	
	public function cmd_sl_partgame(GWS_Message $msg)
	{
		
	}
	
	
}

