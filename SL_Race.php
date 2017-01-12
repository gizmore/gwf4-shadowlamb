<?php
final class SL_Race
{
	public static function races() { return array_keys(self::$RACES); }
	public static function enumRaces() { return array_merge(array('none'), self::races()); }
	public static function npcRaces() { return array_slice(self::races(), array_search('gremlin', self::races())+1); }
	public static function playerRaces() { return array_slice(self::races(), 0, array_search('gremlin', self::races())+1); }
	public static function randomHumanRace() { return SL_Global::randomItem(self::playerRaces()); }
	public static function validPlayerRace($race) { return in_array($race, self::playerRaces(), true); }
	public static function getBonus($race) { return self::$RACES[$race]; }
	public static function isMagicRace($race) { $b = self::getBonus($race); return $b['base_mp'] > 0; }
	
	/**
	 * Bonus values for races.
	 */
	public static $RACES = array(
		'fairy' =>     array('base_hp'=>0,'base_mp'=> 5,'strength'=>-2,'dexterity'=>3,'wisdom'=>4,'intelligence'=>4),
		'elve' =>      array('base_hp'=>1,'base_mp'=> 4,'strength'=>-1,'dexterity'=>3,'wisdom'=>2,'intelligence'=>3),
		'halfelve' =>  array('base_hp'=>1,'base_mp'=> 3,'strength'=> 0,'dexterity'=>3,'wisdom'=>2,'intelligence'=>2),
		'vampire' =>   array('base_hp'=>0,'base_mp'=> 3,'strength'=> 0,'dexterity'=>4,'wisdom'=>2,'intelligence'=>3),
		'darkelve' =>  array('base_hp'=>1,'base_mp'=> 2,'strength'=> 0,'dexterity'=>3,'wisdom'=>2,'intelligence'=>2),
		'woodelve' =>  array('base_hp'=>1,'base_mp'=> 1,'strength'=> 0,'dexterity'=>3,'wisdom'=>1,'intelligence'=>2),
		'human' =>     array('base_hp'=>2,'base_mp'=> 0,'strength'=> 0,'dexterity'=>3,'wisdom'=>1,'intelligence'=>2),
		'gnome' =>     array('base_hp'=>2,'base_mp'=> 0,'strength'=> 0,'dexterity'=>3,'wisdom'=>1,'intelligence'=>2),
		'dwarf' =>     array('base_hp'=>3,'base_mp'=> 0,'strength'=> 1,'dexterity'=>2,'wisdom'=>1,'intelligence'=>2),
		'halfork' =>   array('base_hp'=>3,'base_mp'=>-1,'strength'=> 1,'dexterity'=>2,'wisdom'=>1,'intelligence'=>2),
		'halftroll' => array('base_hp'=>3,'base_mp'=>-2,'strength'=> 2,'dexterity'=>2,'wisdom'=>0,'intelligence'=>1),
		'ork' =>       array('base_hp'=>4,'base_mp'=>-3,'strength'=> 3,'dexterity'=>1,'wisdom'=>1,'intelligence'=>1),
		'troll' =>     array('base_hp'=>4,'base_mp'=>-4,'strength'=> 4,'dexterity'=>0,'wisdom'=>0,'intelligence'=>0),
		'gremlin' =>   array('base_hp'=>4,'base_mp'=>-5,'strength'=> 3,'dexterity'=>1,'wisdom'=>0,'intelligence'=>0),
		#NPC
		'animal' =>    array('base_hp'=>1,'base_mp'=> 5,'strength'=> 1,'dexterity'=>0,'wisdom'=>0,'intelligence'=>0),
		'droid' =>     array('base_hp'=>4,'base_mp'=> 0,'strength'=> 2,'dexterity'=>0,'wisdom'=>0,'intelligence'=>0),
		'dragon' =>    array('base_hp'=>8,'base_mp'=> 8,'strength'=> 8,'dexterity'=>0,'wisdom'=>8,'intelligence'=>8),
	);
}
