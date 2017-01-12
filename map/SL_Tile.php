<?php
abstract class SL_Tile
{
	const GEN_WALL    = 0x40;
	const GEN_VISITED = 0x20;
	
	const WALL = 0x0F;
	const SEA = 0x0E;

	const UNKNOWN = 0x00;
	const DUST = 0x01;
	const GRASS = 0x02;
	const STONE = 0x03;
	const WATER = 0x04;
	
	public static function type($tile) { return $tile & 0x1F; }
	
	public static function genWall($tile) { return ($tile & self::GEN_WALL) > 0; }
	public static function genVisited($tile) { return ($tile & self::GEN_VISITED) > 0; }
	
	public static function canLook($tile) { return self::type($tile) !== self::WALL; }
	
	
}