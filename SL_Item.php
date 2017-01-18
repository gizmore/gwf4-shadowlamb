<?php
require_once 'item/SL_Usable.php';
require_once 'item/SL_Edible.php';
require_once 'item/SL_Equipment.php';
require_once 'item/SL_Armor.php';
require_once 'item/SL_Weapon.php';

class SL_Item extends GDO
{
	public static $CACHE = array();
	
	public static $SLOTS = array('air', 'floor', 'hand',  'inventory',  'helmet', 'weapon', 'shield', 'boots', 'armor', 'ring', 'amulet');
	public static function numSlots() { return count(self::$SLOTS); }
	public static function slotsEnum() { return array_merge(array('none'), self::$SLOTS); }
	public static function slotEnum($int) { return self::$SLOTS[$int-1]; }
	public static function validSlotInt($int) { return ($int > 0) && ($slot <= self::numSlots()); }
	public static function validPlayerSlotInt($int) { return ($int > 3) && ($int <= self::numSlots()); }
	public static function slotInt($slot) { $i = array_search($slot, self::$SLOTS, true); return $i === false ? 0 : $i+1; }
	
	public $x, $y, $z;
	
	###########
	### GDO ###
	###########
	public function getClassName() { return __CLASS__; }
	public function getTableName() { return GWF_TABLE_PREFIX.'sl_items'; }
	public function getColumnDefines()
	{
		return array(
			'i_id' => array(GDO::AUTO_INCREMENT),
			'i_uid' => array(GDO::UINT|GDO::INDEX, GDO::NULL),
			'i_slot' => array(GDO::ENUM, GDO::NOT_NULL, self::slotsEnum()),
			'i_name' => array(GDO::VARCHAR|GDO::ASCII|GDO::CASE_S, GDO::NOT_NULL, 32),
				
			'i_attack' => array(GDO::TINY|GDO::UINT, 0),
			'i_defense' => array(GDO::TINY|GDO::UINT, 0),
			'i_damage' => array(GDO::TINY|GDO::UINT, 0),
			'i_armor' => array(GDO::TINY|GDO::UINT, 0),
				
			'i_strength' => array(GDO::TINY|GDO::UINT, 0),
			'i_dexterity' => array(GDO::TINY|GDO::UINT, 0),
			'i_wisdom' => array(GDO::TINY|GDO::UINT, 0),
			'i_intelligence' => array(GDO::TINY|GDO::UINT, 0),
				
			'i_fighter' => array(GDO::TINY|GDO::UINT, 0),
			'i_ninja' => array(GDO::TINY|GDO::UINT, 0),
			'i_priest' => array(GDO::TINY|GDO::UINT, 0),
			'i_wizard' => array(GDO::TINY|GDO::UINT, 0),

			# Joins
			'player' => array(GDO::JOIN, GDO::NOT_NULL, array('SL_Player', 'p_uid', 'i_uid')),
		);
	}
	
	##############
	### Getter ###
	##############
	public function base($field) { return $this->getVar('i_'.$field); }
	public function getID() { return $this->getVar('i_id'); }
	public function getUserID() { return $this->getVar('i_uid'); }
	public function getSlot() { return $this->getVar('i_slot'); }
	public function getSlotInt() { return self::slotInt($this->getSlot()); }
	public function getName() { return $this->getVar('i_name'); }
	public function getNameInt() { return array_search($this->getName(), self::itemNames(), true); }
	public function getStats() { return self::itemStats($this->getName()); }
	public function getStat($stat) { $stats = $this->getStats(); return $stats[$stat]; }
	public function getWeight() { return $this->getStat(1); }
	public function equipmentSlot() { return 'inventory'; }
	public function onFloor() { return $this->getSlot() === 'floor'; }
	
	#############
	### Cache ###
	#############
	public static function getCached($itemId)
	{
		return isset(self::$CACHE[$itemId]) ? self::$CACHE[$itemId] : null;
	}
	
	##############
	### Setter ###
	##############
	public function setPlayer($player, $slot)
	{
		return $this->saveVars(array(
			'i_uid'	=> $player ? $player->getID() : '0',
			'i_slot' => $slot,
		));
	}
	
	###############
	### Payload ###
	###############
	public function payload()
	{
		$payload = GWS_Message::wr32($this->getID());
		$payload .= GWS_Message::wr8($this->x);
		$payload .= GWS_Message::wr8($this->y);
		$payload .= GWS_Message::wr8($this->z);
// 		$payload .= GWS_Message::wr32($this->getUserID());
		$payload .= GWS_Message::wr8($this->getSlotInt());
		$payload .= GWS_Message::wr16($this->getNameInt());
		$payload .= GWS_Message::wr16($this->getWeight());
		foreach (SL_Player::itemFields() as $field)
		{
			$payload .= GWS_Message::wr8($this->base($field));
		}
		return $payload;
	}
	
	##############
	### Loader ###
	##############
	public static function loadItems(SL_Player $player)
	{
		$table = self::table(__CLASS__);
		$result = $table->select('*', 'i_uid='.$player->id);
		while (false !== ($data = $table->fetch($result, GDO::ARRAY_A)))
		{
			$itemclass = self::itemClass($data['i_name']);
			$item = new $itemclass($data);
			self::$CACHE[$item->getID()] = $item;
			$player->loadedItem($item);
		}
	}
	
	###############
	### Factory ###
	###############
	private static function itemclass($name) { $data = self::itemStats($name); return 'SL_'.$data[0]; }
	public static function itemNames() { return array_keys(self::itemsStats()); }
	private static function itemsStats() { static $STATS; if (!$STATS) { $STATS = require('item/itemdata.php'); } return $STATS; }
	private static function itemStats($name) { $STATS = self::itemsStats(); return $STATS[$name]; }
	public static function factoryRandom()
	{
		$names = array_keys(self::itemsStats());
		return self::factory($names[array_rand($names)]);
	}
	
	public static function factory($name)
	{
		$stats = self::itemStats($name);
		$itemclass = self::itemclass($name);
		$stat = 2;
		$item = new $itemclass(array(
			'i_id' => '0',
			'i_uid' => '0',
			'i_slot' => 'floor',
			'i_name' => $name,
				
			'i_attack' => self::diceStats($stats, $stat++),
			'i_defense' => self::diceStats($stats, $stat++),
			'i_damage' => self::diceStats($stats, $stat++),
			'i_armor' => self::diceStats($stats, $stat++),
				
			'i_strength' => self::diceStats($stats, $stat++),
			'i_dexterity' => self::diceStats($stats, $stat++),
			'i_wisdom' => self::diceStats($stats, $stat++),
			'i_intelligence' => self::diceStats($stats, $stat++),
				
			'i_fighter' => self::diceStats($stats, $stat++),
			'i_ninja' => self::diceStats($stats, $stat++),
			'i_priest' => self::diceStats($stats, $stat++),
			'i_wizard' => self::diceStats($stats, $stat++),
		));
		if ($item->insert())
		{
			self::$CACHE[$item->getID()] = $item;
			return $item;
		}
		return  null;
	}
	
	private static function diceStats(array $stats, $index)
	{
		if (!isset($stats[$index]))
		{
			return 0;
		}
		$minmax = explode('-', $stats[$index]);
		if (count($minmax) === 1)
		{
			return (int)$minmax;
		}
		return SL_Global::rand($minmax[0], $minmax[1]);
	}
}
