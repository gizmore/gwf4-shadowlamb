<?php
require_once 'magic/SL_Spell.php';
require_once 'magic/SL_Potion.php';
require_once 'SL_Obstacle.php';
require_once 'SL_Item.php';
require_once 'SL_Levelup.php';
require_once 'SL_Race.php';
require_once 'SL_Player.php';
require_once 'SL_Bot.php';
require_once 'map/SL_Games.php';
require_once 'SL_PlayerFactory.php';
require_once 'fx/SL_Effect.php';
require_once 'fx/SL_Throw.php';
require_once 'combat/SL_AttackFactory.php';

final class Module_Shadowlamb extends GWF_Module
{
	private static $instance;
	public static function instance() { return self::$instance; }
	
	private $runes;
	
	##################
	### GWF_Module ###
	##################
	public function getVersion() { return 4.00; }
	public function getDefaultPriority() { return 64; }
	public function getDefaultAutoLoad() { return true; }
	public function getClasses() { return array('SL_Player', 'SL_Item'); }
	public function onLoadLanguage() { return $this->loadLanguage('lang/shadowlamb'); }
	public function onInstall($dropTable) { require_once 'SL_Install.php'; return SL_Install::onInstall($this, $dropTable); }

	##############
	### Config ###
	##############
	public function cfgRuneconfig() { return $this->initRunes(); }
	public function cfgLevels() { $r = $this->initRunes(); return $r['levels']; }
	public function cfgRunes() { $r = $this->initRunes(); return $r['runes']; }
	public function cfgRunecost() { $r = $this->initRunes(); return $r['runecost']; }
	public function cfgWelcomeMessage() { return $this->getModuleVar('sl_welcome_msg', 'Shadowlamb v6.1'); }
	public function cfgBots() { return $this->getModuleVarBool('sl_bots', '1'); }
	public function cfgMaxBots() { return $this->getModuleVarInt('sl_max_bots', '6'); }
	public function cfgMaxInvSlots() { return $this->getModuleVarInt('sl_max_inv_slots', '30'); }
	
	public function cfgMaxFoodclanBots() { return $this->getModuleVarInt('sl_max_foodclan_bots', '2'); }
	public function cfgMaxAssassinBots() { return $this->getModuleVarInt('sl_max_assassin_bots', '1'); }
	public function cfgMaxNimdaBots() { return $this->getModuleVarInt('sl_max_nimda_bots', '0'); }
	public function cfgMaxRobberBots() { return $this->getModuleVarInt('sl_max_loser_bots', '0'); }
	
	###############
	### Startup ###
	###############
	public function onStartup()
	{
		self::$instance = $this;
		$this->onLoadLanguage();
	
		if ( (!Common::isCLI()) && (!GWF_Website::isAjax()) )
		{
			GWF_Website::addJavascriptInline($this->getTGCConfigJS());
				
			$this->onInclude();
				
			switch (GWF_DEFAULT_DESIGN)
			{
				default:
				case 'sl-web':
					$this->includeWebAssets();
					break;
	
				case 'default':
				case 'sl-app':
					$this->includeAppAssets();
					break;
			}
		}
	}
	
	private function initRunes()
	{
		if (!$this->runes)
		{
			$path1 = GWF_PATH.'module/Shadowlamb/sl_config.php';
			$path2 = GWF_PATH.'module/Shadowlamb/sl_config_example.php';
			$path = GWF_File::isFile($path1) ? $path1 : $path2;
			$this->runes = require($path);
		}
		return $this->runes;
	}
	
	##############
	### Assets ###
	##############
	private function getTGCConfigJS()
	{
		$this->initRunes();
		$runes = json_encode($this->runes['runes']);
		$runecost = json_encode($this->runes['runecost']);
		$levels = json_encode($this->runes['levels']);
		$version = $this->getVersion();
		$races = json_encode(SL_Race::races());
		$colors = json_encode(SL_Player::$COLORS);
		$elements = json_encode(SL_Player::$ELEMENTS);
		$combat = json_encode(SL_Player::$COMBAT);
		$attributes = json_encode(SL_Player::$ATTRIBUTES);
		$skills = json_encode(SL_Player::$SKILLS);
		$slots = json_encode(SL_Item::slots());
		$eqslots = json_encode(SL_Item::$EQUIPMENT_SLOTS);
		$items = json_encode(SL_Item::itemNames());
		$config = json_encode(array(
			'maxInvSlots' => $this->cfgMaxInvSlots(),
		));
		return sprintf('window.SL_CONFIG = { setting: %s, levels: %s, runes: %s, runecost: %s, version: %0.2f, races: %s, colors: %s, elements: %s, combat: %s, attributes: %s, skills: %s, slots: %s, eqslots: %s, items: %s };',
				$config, $levels, $runes, $runecost, $version, $races, $colors, $elements, $combat, $attributes, $skills, $slots, $eqslots, $items);
	}
	
	private function includeWebAssets()
	{
		$this->addCSS('shadowlamb-site.css');
	}
	
	private function includeAppAssets()
	{
		# Libs
		$v = $this->getVersionDB(); $min = GWF_DEBUG_JS ? '' : '.min';
		GWF_Website::addJavascript(GWF_WEB_ROOT."module/Tamagochi/bower_components/howler.js/dist/howler$min.js?v=$v");
	
		# CSS
		$this->addCSS('shadowlamb.css');
		# Babylon WebGL
		$this->addJavascript('lib/babylon.custom.js');
		# jQuery
		$this->addJavascript('jq/px_to_em.jquery.js');
		# Conf
		$this->addJavascript('conf/sl-conf.js');
		# Filters
// 		$this->addJavascript('filters/gwf-date-filter.js');
		# Directives
		$this->addJavascript('directives/sl-stat-bar.js');
		# Model
		$this->addJavascript('model/sl-player.js');
		$this->addJavascript('model/sl-item.js');
		$this->addJavascript('model/sl-floor.js');
		$this->addJavascript('model/sl-map.js');
		# Ctrl
		$this->addJavascript('ctrl/sl-ctrl.js');
		$this->addJavascript('ctrl/sl-spell-ctrl.js');
		$this->addJavascript('ctrl/sl-gamelist-ctrl.js');
		$this->addJavascript('ctrl/sl-sidebar-ctrl.js');
		# Srvc
		$this->addJavascript('srvc/sl-babylon-srvc.js');
		$this->addJavascript('srvc/sl-player-srvc.js');
		$this->addJavascript('srvc/sl-effect-srvc.js');
		# Dialog
// 		$this->addJavascript('dlg/sl-inventory-dlg.js');
// 		$this->addJavascript('dlg/tgc-levelup-dialog.js');
// 		$this->addJavascript('dlg/tgc-player-dialog.js');
// 		$this->addJavascript('dlg/tgc-spell-dialog.js');
// 		# Util
// 		$this->addJavascript('util/tgc-rand-util.js');
// 		$this->addJavascript('util/tgc-level-util.js');
// 		$this->addJavascript('util/tgc-color-util.js');
// 		$this->addJavascript('util/tgc-map-util.js');
// 		$this->addJavascript('util/tgc-shape-util.js');
	}
	
	###############
	### Sidebar ###
	###############
	public function sidebarContent($bar)
	{
		if ($bar === 'left')
		{
			return $this->sidebarTemplate();
		}
	}
	
	private function sidebarTemplate()
	{
		$tVars = array(
			'href_game' => GWF_WEB_ROOT.'shadowlamb-game',
		);
		return $this->template('sidebar.php', $tVars);
	}
	
}
