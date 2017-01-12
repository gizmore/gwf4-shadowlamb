<?php
require_once 'SL_Race.php';

class SL_Player extends GDO
{
	const VIEW_LENGTH = 5;
	
	public static $USER_FIELDS = array('user_name', 'user_guest_name', 'user_gender', 'user_regdate', 'user_level', 'user_credits');
	public static function userFields() { return '*, '.implode(',', self::$USER_FIELDS); }
	public static $JOINS = array('user');
	
	public static $GENDERS = array('male', 'female');
	public static $MODES = array('attack', 'defend');
	public static $COLORS = array('red', 'black', 'blue', 'green');
	public static $ELEMENTS = array('earth', 'wind', 'water', 'fire');
	public static $STATS = array('hp', 'mp', 'base_hp', 'base_mp');
	public static $SKILLS = array('fighter', 'ninja', 'priest', 'wizard');
	public static $XP = array('fighter_xp', 'ninja_xp', 'priest_xp', 'wizard_xp');
	public static $ATTRIBUTES = array('strength', 'dexterity', 'wisdom', 'intelligence');
	public static function allFields() { return array_merge(self::$XP, self::$SKILLS, self::$STATS, self::$ATTRIBUTES); }
	
	public static function colorsEnum() { return array_merge(array('none', self::$COLORS)); }
	public static function elementsEnum() { return array_merge(array('none', self::$ELEMENTS)); }
	public static function modesEnum() { return array_merge(array('none', self::$MODES)); }
	public static function skillsEnum() { return array_merge(array('none', self::$SKILLS)); }
	
	public function raceInt() { return self::enumToInt($this->getRace(), SL_Race::races()); }
	public function genderInt() { return self::enumToInt($this->getGender(), self::$GENDERS); }
	public function elementInt() { return self::enumToInt($this->getElement(), self::$ELEMENTS); }
	public function colorInt() { return self::enumToInt($this->getColor(), self::$COLORS); }
	public function skillInt() { return self::enumToInt($this->getSkill(), self::$SKILLS); }
	public static function enumToInt($value, array $enum) { $index = array_search($value, $enum, true); return $index === false ? 0 : $index + 1; }
	
	public static $FEELS = array('health', 'endurance', 'sober', 'awake', 'brave', 'satiness', 'drought', 'carry');
	private $water = 100, $tired = 0, $food = 100, $alc = 0, $frightened = 0, $endurance = 0;

	private $user = null, $baseLevel = 1, $adjustedLevel = 1;

	
	public $game, $x, $y, $z;

	private $base = array();
	private $effects = array();
	private $adjusted = array();

	public function getClassName() { return __CLASS__; }
	public function getTableName() { return GWF_TABLE_PREFIX.'sl_players'; }
	public function getColumnDefines()
	{
		return array(
				'p_uid' => array(GDO::PRIMARY_KEY|GDO::UINT),
				'p_type' => array(GDO::VARCHAR|GDO::ASCII|GDO::CASE_S, GDO::NULL, 16),
				'p_race' => array(GDO::ENUM, 'none', SL_Race::enumRaces()),

				# Base
				'p_gold' => array(GDO::UINT, 50),

				'p_hp' => array(GDO::MEDIUM|GDO::UINT, 0),
				'p_mp' => array(GDO::MEDIUM|GDO::UINT, 0),
				'p_base_hp' => array(GDO::MEDIUM|GDO::UINT, 0),
				'p_base_mp' => array(GDO::MEDIUM|GDO::UINT, 0),

				'p_strength' => array(GDO::MEDIUM|GDO::UINT, 0),
				'p_dexterity' => array(GDO::MEDIUM|GDO::UINT, 0),
				'p_wisdom' => array(GDO::MEDIUM|GDO::UINT, 0),
				'p_intelligence' => array(GDO::MEDIUM|GDO::UINT, 0),
					
				'p_fighter' => array(GDO::TINY|GDO::UINT, 0),
				'p_ninja' => array(GDO::TINY|GDO::UINT, 0),
				'p_priest' => array(GDO::TINY|GDO::UINT, 0),
				'p_wizard' => array(GDO::TINY|GDO::UINT, 0),

				# Combat
				'p_fighter_xp' => array(GDO::UINT, 0),
				'p_ninja_xp' => array(GDO::UINT, 0),
				'p_priest_xp' => array(GDO::UINT, 0),
				'p_wizard_xp' => array(GDO::UINT, 0),

				'p_active_color' => array(GDO::ENUM, 'none', self::colorsEnum()),
				'p_active_element' => array(GDO::ENUM, 'none', self::elementsEnum()),
				'p_active_skill' => array(GDO::ENUM, 'none', self::skillsEnum()),
				'p_active_mode' => array(GDO::ENUM, 'none', self::modesEnum()),
					
				# Timestamps
				'p_last_color_change' => array(GDO::UINT, GDO::NULL),
				'p_last_element_change' => array(GDO::UINT, GDO::NULL),
				'p_last_skill_change' => array(GDO::UINT, GDO::NULL),
				'p_last_mode_change' => array(GDO::UINT, GDO::NULL),
				'p_last_activity' => array(GDO::UINT, GDO::NULL),

				# Joins
				'user' => array(GDO::JOIN, GDO::NOT_NULL, array('GWF_User', 'p_uid', 'user_id')),
		);
	}

	##############
	### Static ###
	##############
	
	############
	### Move ###
	############
	public function setGame($game)
	{
		$this->game = $game;
		$this->x = $this->y = $this->z = 0;
	}
	
	public function setToFloorIndex(SL_Floor $floor, $index)
	{
		$this->x = $floor->x($index);
		$this->y = $floor->y($index);
		$this->z = $floor;
	}
	
	public function requestFirstMap()
	{
		$x = $this->x; $y = $this->y; $r = self::VIEW_LENGTH;
		$this->requestMap($x-$r, $y-$r, $x+$r, $y+$r);
	}
	
	private static $DIR_X = array('N' => 0, 'S' => 0, 'E' => 1, 'W' => -1);
	private static $DIR_Y = array('N' => -1, 'S' => 1, 'E' => 0, 'W' => 0);
	public function move($direction)
	{
		$xoff = self::$DIR_X[$direction];
		$yoff = self::$DIR_Y[$direction];
		if (!$this->z->canMove($this->x + $xoff, $this->y + $yoff))
		{
			return $this->sendError(SL_Commands::ERR_WAY_BLOCKED);
		}
		$this->x += $xoff;
		$this->y += $yoff;
		$this->afterMove($direction);
	}
	
	private function afterMove($direction)
	{
		$x = $this->x; $y = $this->y; $r = self::VIEW_LENGTH;
		switch ($direction)
		{
			case 'N': case 'S': $this->requestMap($x-$r, $y, $x+$r, $y); break;
			case 'E': case 'W': $this->requestMap($x, $y-$r, $x, $y+$r); break;
		}
		$this->game->sendBinary($this->payloadPos());
	}
	
	private function requestMap($x1, $y1, $x2, $y2)
	{
		$this->sendBinary($this->z->payloadMap($x1, $y1, $x2, $y2));
	}
	
	################
	### Messages ###
	################
	public function conn() { return GWS_Global::getConnectionInterface($this); }
	public function sendError($code) { $this->sendBinary(GWS_Message::wr16(0x0000).GWS_Message::wr16($code)); }
	public function sendText($payload) { printf("%s << %s\n", $this->displayName(), $payload); $this->conn()->send($payload); }
	public function sendBinary($payload) { printf("%s << BIN\n", $this->displayName()); GWS_ServerUtil::hexdump($payload); $this->conn()->sendBinary($payload); }
	###############
	### Payload ###
	###############
	public function payloadPos()
	{
		return
			GWS_Message::wr16(SL_Commands::SRV_POS).
			GWS_Message::wr32($this->getID()).
			GWS_Message::wr8($this->x).GWS_Message::wr8($this->y).GWS_Message::wr8($this->z->z());
	}
	
	public function payloadOwn()
	{
		return GWS_Message::wr16(SL_Commands::SRV_OWN).$this->payloadBase().$this->payloadExtended().$this->payloadItems().$this->payloadEffects();
	}
	
	public function payloadOther()
	{
		return GWS_Message::wr16(SL_Commands::SRV_PLAYER).$this->payloadBase();
	}
	
	private function payloadBase()
	{
		$payload  = GWS_Message::wr32($this->getID()).GWS_Message::wrS($this->displayName());
		$payload .= GWS_Message::wr8(self::raceInt());
		$payload .= GWS_Message::wr8(self::genderInt());
		$payload .= GWS_Message::wr8(self::elementInt());
		$payload .= GWS_Message::wr8(self::colorInt());
		$payload .= GWS_Message::wr8(self::skillInt());
		
		$payload .= GWS_Message::wr16($this->baseLevel).GWS_Message::wr16($this->adjustedLevel);
		$payload .= GWS_Message::wr16($this->hp()).GWS_Message::wr16($this->mp());
		$payload .= GWS_Message::wr16($this->maxHP()).GWS_Message::wr16($this->maxMP());

		foreach (self::$ATTRIBUTES as $field)
		{
			$payload .= GWS_Message::wr8($this->base($field));
			$payload .= GWS_Message::wr8($this->power($field));
		}
		foreach (self::$SKILLS as $field)
		{
			$payload .= GWS_Message::wr8($this->base($field));
			$payload .= GWS_Message::wr8($this->power($field));
		}
		return $payload;
	}
	
	private function payloadExtended()
	{
		$payload = '';
		foreach (self::$SKILLS as $field)
		{
			$payload .= GWS_Message::wr32((int)$this->getVar('p_'.$field.'_xp'));
		}
		return $payload;
	}
	
	private function payloadEffects()
	{
		return '';
	}
	
	private function payloadItems()
	{
		return '';
	}
	
	############
	### User ###
	############
	public function getID() { return $this->getVar('p_uid'); }
	public function getUserID() { return $this->getVar('p_uid'); }
	public function setUser(GWF_User $user) { $this->user = $user; }
	public function getUser() { return $this->user; }
	public function isBot() { return $this->user->isBot(); }
	public function isHuman() { return !$this->user->isBot(); }
	public function displayName() { return $this->getUser()->displayName(); }

	###############
	### Friends ###
	###############
	public function isEnemy(SL_Player $player) { return !$this->isFriendly($player); }
	public function isFriendly(SL_Player $player) { return $player === $this || $this->isFriend($player); }
	public function isFriend(SL_Player $player) { return GWF_Friendship::areFriendsByID($this->getUserID(), $player->getUserID()); }

	###############
	### Getters ###
	###############
	public function getName() { return $this->getVar('user_name'); }
	public function isMagicRace() { return SL_Race::isMagicRace($this->getRace()); }
	public function getRace() { $race = $this->getVar('p_race'); return $race === 'none' ? 'human' : $race; }
	public function getGender() { return $this->getVar('user_gender'); }
	public function getColor() { return $this->getVar('p_active_color'); }
	public function getElement() { return $this->getVar('p_active_element'); }
	public function getMode() { return $this->getVar('p_active_mode'); }
	public function getSkill() { return $this->getVar('p_active_skill'); }
	
	public function isDead() { return $this->hp() <= 0; }
	public function giveHP($hp) { $this->base['hp'] = Common::clamp($this->hp() + $hp, 0, $this->maxHP()); $this->feel('health'); }
	public function giveMP($mp) { $this->base['mp'] = Common::clamp($this->mp() + $mp, 0, $this->maxMP()); }

	public function hp() { return $this->base['hp']; }
	public function maxHP() { return $this->power('max_hp'); }
	public function mp() { return $this->base['mp']; }
	public function maxMP() { return $this->power('max_mp'); }

	public function food() { return $this->food; }
	public function giveFood($food) { $this->food = Common::clamp($this->food + $food, 0, $this->maxFood()); }
	public function maxFood() { return 100; }
	public function water() { return $this->water; }
	public function giveWater($water) { $this->water = Common::clamp($this->water + $water, 0, $this->maxWater()); }
	public function maxWater() { return 10 + $this->priestLevel() * 4; }
	public function gold() { return $this->getVar('p_gold'); }

	public function sumSkills() { return $this->fighter() + $this->ninja() + $this->priest() + $this->wizard(); }
	public function sumAttributes() { return $this->strength() + $this->dexterity() + $this->wisdom() + $this->intelligence(); }
	public function playerLevel() { return $this->baseLevel; }
	public function adjustedLevel() { return $this->adjustedLevel; }
	public function fighterLevel() { return $this->skillLevel('fighter'); }
	public function ninjaLevel() { return $this->skillLevel('ninja'); }
	public function priestLevel() { return $this->skillLevel('priest'); }
	public function wizardLevel() { return $this->skillLevel('wizard'); }
	public function skillLevel($skill) { return $this->getVar('p_'.$skill); }

	public function feel($feel)
	{
		$value = call_user_func(array($this, $feel));
		$this->base[$feel] = $this->adjusted[$feel] = $value;
		return $value;
	}

	#############
	### Score ###
	#############
	public function base($field) { return isset($this->base[$field]) ? $this->base[$field] : 0; }
	public function power($field) { return $this->adjusted[$field]; }
	public function averageBase($field) { return SL_Global::averageBase($field); }
	public function averagePower($field) { return SL_Global::averagePower($field); }

	public function compareTo(SL_Player $player, $field) { return $this->compare($this->power($field), $player->power($field)); }
	public function compareToAvg($field) { return $this->compare($this->power($field), $this->averagePower($field)); }
	public function compare($p1, $p2) { $sum = $p1+$p2; return $sum < 1 ? 1.0 : ($p1+$p1+$p2) / ($p1+$p2+$p2); }

	// 	public function adjust($field, $by) { $this->adjusted[$field] += $by; }

	##############
	### Fields ###
	##############
	public function strength() { return $this->power('strength'); }
	public function dexterity() { return $this->power('dexterity'); }
	public function wisdom() { return $this->power('wisdom'); }
	public function intelligence() { return $this->power('intelligence'); }

	public function fighter() { return $this->power('fighter'); }
	public function ninja() { return $this->power('ninja'); }
	public function priest() { return $this->power('priest'); }
	public function wizard() { return $this->power('wizard'); }

	public function carry() { return 0.0; }
	public function health() { return Common::clamp($this->hp() /  min(30, $this->maxHP()), 0.0, 1.0); }
	public function endurance() { return Common::clamp($this->endurance / 60.0, 0.25, 1.0); }
	public function sober() { return 1.0; }
	public function brave() { return 1.0; }
	public function awake() { return Common::clamp($this->tired - 50 / 100.0, 0.0, 1.0); }
	public function drought() { return Common::clamp($this->water / 100.0, 0.0, 1.0); }
	public function satiness() { return Common::clamp($this->food - 50 / 200.0, 0.0, 1.0); }
	public function giveEndurance($endurance) { $this->endurance = Common::clamp($this->endurance + $endurance, 0.0, $this->dexterity()); }

	#############
	### Debug ###
	#############
	public function debugInfo()
	{
		$fields1 = array(
				'fighter_xp',    'ninja_xp',    'priest_xp',    'wizard_xp',
				'fighter',       'ninja',       'priest',       'wizard',
		);
		$fields2 = array(
				'hp',           'max_hp',
				'mp',           'max_mp',
				'strength',     'dexterity',  'wisdom',      'intelligence',
		);
		$fields3 = self::$FEELS;
		return $this->debugInfoFields($fields1).$this->debugInfoFields($fields2).$this->debugInfoFields($fields3);
	}

	public function debugInfoFields(array $fields)
	{
		$powers = [];
		foreach ($fields as $field)
		{
			$powers[] = sprintf('%s: %s(%s)', $field, $this->base($field), $this->power($field));
		}
		return sprintf('%s: %s', $this->displayName(), implode(' - ', $powers))."\n";
	}

	##############
	### Create ###
	##############
	public static function getByID($id) { return self::getByWhere("p_uid=".intval($id)); }
	public static function getByName($name) { return self::getByWhere(sprintf("user_name='%s'", self::escape($name))); }
	public static function getByWhere($where)
	{
		if ($player = GDO::table(__CLASS__)->selectFirstObject(self::userFields(), $where, '', '', array('user')))
		{
			$player->afterLoad();
		}
		return $player;
	}
	
	##############
	### Rehash ###
	##############
	public function afterLoad()
	{
		$this->base = array();
		$this->adjusted = array();
		$this->rehash();
		$this->respawn();
	}

	public function rehash()
	{
		$this->rehashBase();
		$this->rehashSkills();
		$this->rehashAtrributes();
		$this->rehashRace();
		$this->rehashStats();
		$this->rehashFeels();
		$this->rehashLevel();
	}

	private function rehashLevel()
	{
		$this->baseLevel = 1;
		foreach (self::$SKILLS as $skill)
		{
			$this->baseLevel += $this->skillLevel($skill);
		}
		$this->adjustedLevel = $this->baseLevel * 4;
		$this->adjustedLevel += $this->sumAttributes();
	}

	private function rehashBase()
	{
		foreach ($this->allFields() as $field)
		{
			$this->base[$field] = (int)$this->getVar('p_'.$field);
		}

		foreach ($this->base as $field => $value)
		{
			$this->adjusted[$field] = $value;
		}
	}

	private function rehashSkills()
	{
		$this->rehashSkill('fighter');
		$this->rehashSkill('ninja');
		$this->rehashSkill('priest');
		$this->rehashSkill('wizard');
	}

	private function rehashAtrributes()
	{
		$this->adjusted['strength'] += $this->fighter();
		$this->adjusted['dexterity'] += $this->ninja();
		$this->adjusted['wisdom'] += $this->priest();
		$this->adjusted['intelligence'] += $this->wizard();
	}

	private function rehashRace()
	{
		foreach (SL_Race::getBonus($this->getRace()) as $field => $bonus)
		{
			$this->adjusted[$field] += $bonus;
		}
	}

	private function rehashStats()
	{
		$this->adjusted['base_hp'] += $this->strength() * 3 + $this->dexterity() * 1;
		$this->adjusted['base_mp'] += $this->wisdom() * 1 + $this->intelligence() * 2;
		$this->adjusted['max_hp'] = $this->adjusted['base_hp'];
		$this->adjusted['max_mp'] = $this->adjusted['base_mp'];
	}

	private function rehashFeels()
	{
		foreach (self::$FEELS as $feel)
		{
			$this->feel($feel);
		}
	}

	private function rehashSkill($skill)
	{
		$xp = $this->getVar('p_'.$skill.'_xp');
		$oldLevel = $this->base[$skill];
		$newLevel = SL_Levelup::levelForXP($xp);
		if ($oldLevel != $newLevel)
		{
			$this->base[$skill] = $newLevel;
			$this->adjusted[$skill] = $newLevel;
			if ($this->saveVar('p_'.$skill, $newLevel.''))
			{
				return $newLevel - $oldLevel;
			}
		}
		return false;
	}

	###############
	### Levelup ###
	###############
	public function giveXP($skill, $xp, $announce=true)
	{
		$xp = (int)$xp;
		if ($xp > 0)
		{
			$this->increase('p_'.$skill.'_xp', $xp);
			if (false !== ($levelDiff = $this->rehashSkill($skill)))
			{
				SL_Levelup::onLevelup($this, $skill, $levelDiff);
				if ($announce)
				{
					$payload = json_encode($this->ownPlayerDTO());
					$this->sendCommand('SL_LVLUP', $payload);
				}
			}
		}
	}

	############
	### Kill ###
	############
	public function deletePlayer()
	{
		if ($this->delete())
		{
			$this->gameOver();
			SL_Global::removePlayer($this);
			return true;
		}
	}

	public function killedBy(SL_Player $killer)
	{
		$this->respawn();
	}

	public function respawn()
	{
		$this->base['hp'] = $this->maxHP();
		$this->base['mp'] = $this->maxMP();
		$this->giveHP(0); $this->giveMP(0);
		$this->water = ceil($this->maxWater()/2);
		$this->food = ceil($this->maxFood()/2);
		$this->endurance = $this->dexterity();
		$this->rehashFeels();
	}

	public function getLoot()
	{
		return array();
	}

	public function giveLoot(array $loot)
	{
		$this->food += $loot['food'];
		$this->water += $loot['water'];
		return $this->increase('p_gold', $loot['gold']);
	}

	public function killXP(SL_Player $killer)
	{
		if ($killer->isBot())
		{
			return 1 + $this->playerLevel();
		}
		else
		{
			return ceil($this->adjustedLevel() / 5);
		}
	}

	public function gameOver()
	{
		$payload = $this->ownPlayerDTO();
		$this->sendCommand('SL_GAMEOVER', json_encode($payload));
	}


	############
	### Tick ###
	############
	public function tick($tick)
	{
		$this->giveEndurance($this->ninjaLevel());
		$this->tired = Common::clamp($this->tired+1, 0, 100);
		$this->giveWater(-1);
		$this->giveFood(-1);
		$this->giveHP(ceil($this->fighterLevel()/2));
		$this->giveMP(ceil($this->wizardLevel()/2));
		$this->rehashFeels();
	}

}
