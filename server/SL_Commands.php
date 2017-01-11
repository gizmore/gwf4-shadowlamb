<?php
require_once 'SL_AI.php';
require_once 'SL_Global.php';

final class SL_Commands extends GWS_Commands
{
	private $sl, $acc, $ai;
	
	############
	### Init ###
	############
	public function init()
	{
		GWF_Log::logCron('SL_Commands::init()');
		$this->sl = Module_Tamagochi::instance();
		$this->acc = GWF_Module::loadModuleDB('Account', true, true);
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
	
	public function xcmd_1001(GWS_Message $msg)
	{
		
	}
	
	public function xcmd_start(GWS_Message $msg)
	{
		$msg->replyError(0x0008);
	}

	public function cmd_gamelist(GWS_Message $msg)
	{
		$games = array(
			array('game_id' => 1, 'game_title' => 'abc'),
			array('game_id' => 2, 'game_title' => 'def'),
		);
		$msg->replyText('GAMELIST', json_encode($games));
	}
	
	public function cmd_newgame(GWS_Message $msg)
	{
		
	}
	
	public function cmd_joingame(GWS_Message $msg)
	{
		
	}
	
	public function cmd_partgame(GWS_Message $msg)
	{
		
	}
	
	
}

