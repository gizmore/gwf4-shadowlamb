'use strict';
angular.module('gwf4')
.controller('SLCtrl', function($scope, $document, WebsocketSrvc, CommandSrvc, PlayerSrvc, EffectSrvc, BabylonSrvc) {
	
	SL_Item.BabylonSrvc = BabylonSrvc;
	SL_Item.WebsocketSrvc = WebsocketSrvc;
	
	$scope.data = {
		map: new SL_Map(),
		players: PlayerSrvc.CACHE,
		player: null,
		floor: null,
		showInventory: false,
	};
	

	$scope.$on('$stateChangeSuccess', function(event, toState, toParams, fromState, fromParams) {
//		console.log('SLCtrl.$on-stateChangeSuccess()', toState, toParams);
		$scope.init(toParams.gamename);
	});
	
	$scope.init = function(gamename) {
//		console.log('SLCtrl.init()', gamename);
		$scope.hideGWFContent();
		$scope.closeSidenavs();
		WebsocketSrvc.withConn(function() {
			WebsocketSrvc.sendCommand('sl_joingame', gamename, false).then($scope.joinedGame, $scope.joinFailure);
		});
	};
	
	$scope.joinedGame = function(result) {
		console.log('SLCtrl.joinedGame()', result);
		var data = JSON.parse(result);
		$scope.data.map.init(data.config);
		BabylonSrvc.init();
		BabylonSrvc.rotated();
	};

	$scope.joinFailure = function(error) {
		console.log('SLCtrl.joinFailure()', error);
	};
	
	//////////
	// Move //
	//////////
	$scope.rotate = function(by) { BabylonSrvc.DIR = BabylonSrvc.rotatedDir(by); BabylonSrvc.rotated(); };
	$scope.rotateLeft = function() { $scope.rotate(1); };
	$scope.rotateRight = function() { $scope.rotate(-1); };
	$scope.rotateHalf = function() { $scope.rotate(2); };
	$scope.forward = function() { $scope.move(BabylonSrvc.DIR); };
	$scope.backward = function() { $scope.move(BabylonSrvc.rotatedDir(2)); };
	$scope.strafeLeft = function() { $scope.move(BabylonSrvc.rotatedDir(1)); };
	$scope.strafeRight = function() { $scope.move(BabylonSrvc.rotatedDir(-1)); };

	$scope.move = function(direction) {
		console.log('SLCtrl.move()', direction);
		var gwsMessage = new GWS_Message().cmd(0x2002).sync().write8(direction.charCodeAt(0));
		return WebsocketSrvc.sendBinary(gwsMessage);
	};
	
	$scope.changeFloor = function(number) {
		console.log('SLCtrl.changeFloor()', number);
		$scope.data.floor = $scope.data.map.floor(number);
		$scope.data.map.setCurrentFloor($scope.data.floor);
	};
	
	$scope.onKeyDown = function($event) {
		console.log('SLCtrl.onKeyDown()', $event.keyCode);
		switch($event.keyCode) {
		case 65: return $scope.rotateLeft();
		case 68: return $scope.rotateRight();
		case 87: return $scope.forward();
		case 83: return $scope.backward();
		case 81: return $scope.strafeLeft();
		case 69: return $scope.strafeRight();
		case 73: return $scope.onOpenInventory();
		}
	};
	
	/////////////////////
	// Item management //
	/////////////////////
	$scope.onOpenInventory = function() { $scope.data.showInventory = true; };
	$scope.onCloseInventory = function() { $scope.data.showInventory = false; };
	$scope.onToggleInventory = function() { $scope.data.showInventory = !$scope.data.showInventory; };

	$scope.onClickCanvas = function($event) {
		var yratio = $event.pageY / $('#game-canvas').height();
//		console.log('SLCtrl.onClickCanvas()', $event.pageY, yratio);
		if (yratio > 0.76) {
			$scope.onClickCanvasFloor();
		}
		else {
			$scope.onClickCanvasAir();
		}
	};
	
	$scope.onClickCanvasAir = function() {
		console.log('SLCtrl.onClickCanvasAir()');
		if (SL_PLAYER.hand()) {
			$scope.throwItem(SL_PLAYER.hand());
		}
	};

	$scope.onClickCanvasFloor = function() {
		console.log('SLCtrl.onClickCanvasFloor()');
		if (SL_PLAYER.hand()) {
			$scope.dropItem(SL_PLAYER.hand());
		}
		else {
			var item = $scope.data.floor.pickupItem(SL_PLAYER.x, SL_PLAYER.y);
			if (item) {
				$scope.pickupItem(item);
			}
		}
	};
	
	$scope.pickupItem = function(item) {
		console.log('SLCtrl.pickupItem()', item.name, item);
		var gwsMessage = new GWS_Message().cmd(0x2010).write32(item.id);
		return WebsocketSrvc.sendBinary(gwsMessage);
	};

	$scope.dropItem = function(item) {
		console.log('SLCtrl.dropItem()', item);
		var gwsMessage = new GWS_Message().cmd(0x2011).write32(item.id);
		return WebsocketSrvc.sendBinary(gwsMessage);
	};

	$scope.throwItem = function(item) {
		console.log('SLCtrl.throwItem()', item);
		var gwsMessage = new GWS_Message().cmd(0x2012).write32(item.id).write8(BabylonSrvc.DIR.charCodeAt(0));
		return WebsocketSrvc.sendBinary(gwsMessage);
	};
	
	$scope.onClickAvatar = function(player) {
		console.log('SLCtrl.onClickAvatar()', player);
		$scope.onOpenInventory();
	};

	$scope.onClickLeftHand = function(player) { $scope.onClickHand(player, 'shield'); };
	$scope.onClickRightHand = function(player) { $scope.onClickHand(player, 'weapon'); };
	$scope.onClickHand = function(player, slot) {
		console.log('SLCtrl.onClickHand()', player, slot);
		var hand = SL_PLAYER.hand();
		var gwsMessage = new GWS_Message().cmd(hand?0x2014:0x2015).write32(hand?hand.id:0).write8(SL_Item.slotInt(slot));
		return WebsocketSrvc.sendBinary(gwsMessage);
	};
	
//	//////////
//	// Zoom //
//	//////////
//	$scope.mouseWheel = function($event, $delta, $deltaX, $deltaY) {
//		console.log('SLCtrl.wheel()', $delta, $deltaX, $deltaY);
//		var oldZoom = $scope.data.zoom;
//		$scope.data.zoom = clamp($scope.data.zoom + $deltaY * 0.05, 0.05, 4.00);
//		if ($scope.data.zoom !== oldZoom) {
//			$scope.data.floor.zoomChange($scope.data.zoom - oldZoom);
//			jQuery('sl-game').css('zoom', $scope.data.zoom);
//		}
//	};
//
//	//////////////
//	// Map move //
//	//////////////
//	$scope.mouseDown = function($event) {
////		console.log('SLCtrl.mouseDown()', $event);
//		$event.preventDefault();
//		$scope.data.moved = false;
//		$scope.data.mouseX = $event.pageX;
//		$scope.data.mouseY = $event.pageY;
//	    $document.on('mouseup', $scope.mouseUp);
//	    $document.on('mousemove', $scope.mouseMove);
//	};
//	$scope.mouseUp = function($event) {
////		console.log('SLCtrl.mouseUp()', $scope.data.mouseX - $event.pageX, $scope.data.mouseY - $event.pageY);
//		$document.unbind('mouseup', $scope.mouseUp);
//	    $document.unbind('mousemove', $scope.mouseMove);
//		$scope.moveMap($event);
//	};
//	$scope.mouseMove = function($event) {
////		console.log('SLCtrl.mouseMove()', $scope.data.mouseX - $event.pageX, $scope.data.mouseY - $event.pageY);
//		$scope.data.moved = true;
//		$scope.moveMap($event);
//	};
//	$scope.moveMap = function($event) {
//		var zoom = $scope.data.zoom;
//		var tx = $event.pageX - $scope.data.mouseX;
//		var ty = $event.pageY - $scope.data.mouseY;
//		$scope.data.mouseX = $event.pageX;
//		$scope.data.mouseY = $event.pageY;
//		$scope.data.floor.moveMap(tx / zoom, ty / zoom);
//	};
//	
//	///////////
//	// Click //
//	///////////
//	$scope.clickedMap = function($event) {
//		if (!$scope.data.moved) {
//			var tile = $scope.data.map.clicked($event.pageX, $event.pageY);
//			console.log(tile);
//		}
//	};
	
	//////////////
	// Handlers //
	//////////////
	CommandSrvc.xcmd_2001 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2001 POS()');
		var player = PlayerSrvc.getOrAddPlayer(gwsMessage.read32());
		var oldZ = player.z;
		var x = gwsMessage.read8();
		var y = gwsMessage.read8();
		var z = gwsMessage.read8();
		player.move(x, y, z);
		var newZ = player.z;
		if (oldZ !== newZ) {
			$scope.changeFloor(newZ);
		}
		if (player === SL_PLAYER) {
			BabylonSrvc.setCamera(x, z, y);
		}
		player.init(BabylonSrvc, PlayerSrvc, WebsocketSrvc);
	};
	
	CommandSrvc.xcmd_2002 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2002 OWN()');
		var player = PlayerSrvc.OWN = $scope.data.player = SL_PLAYER = new SL_Player();
		player.id = gwsMessage.read32();
		player.updateOwn(gwsMessage);
		PlayerSrvc.addPlayer(player);
	};

	CommandSrvc.xcmd_2003 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2003 PLAYER()');
		var player = PlayerSrvc.getOrAddPlayer(gwsMessage.read32());
		player.updateOther(gwsMessage);
	};

	CommandSrvc.xcmd_2004 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2004 MAP()');
		$scope.data.map.update(gwsMessage, BabylonSrvc);
	};
	
	CommandSrvc.xcmd_2010 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2010 OUCH()');
		EffectSrvc.ouch();
	};

	CommandSrvc.xcmd_2020 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2020 PICKUP()');
		var player = PlayerSrvc.getOrAddPlayer(gwsMessage.read32());
		var item = $scope.data.floor.removeItem(gwsMessage.read32());
		if (item) {
			SL_PLAYER.handItem(item);
			EffectSrvc.onPickupItem(item);
		}
	};
	
	CommandSrvc.xcmd_2021 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2021 DROP()');
		SL_PLAYER.handItem();
		var player = PlayerSrvc.getOrAddPlayer(gwsMessage.read32());
		var item = SL_Item.getById(gwsMessage.read32());
		if (item) {
			item.x = gwsMessage.read8();
			item.y = gwsMessage.read8();
			item.z = gwsMessage.read8();
			item.restoreCursor();
			item.createMesh();
			$scope.data.floor.addItem(item);
		}
	};
	
	CommandSrvc.xcmd_2022 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2022 THROW()');
		SL_PLAYER.handItem();
		var item = CommandSrvc.flyCommand(gwsMessage, true);
		if (item) {
			item.restoreCursor();
			$scope.data.floor.addItem(item);
		}
	};
	CommandSrvc.xcmd_2023 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2023 FLYING()');
		CommandSrvc.flyCommand(gwsMessage, true);
	};
	CommandSrvc.xcmd_2025 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2025 LAND()');
		CommandSrvc.flyCommand(gwsMessage, false);
	};
	CommandSrvc.flyCommand = function(gwsMessage, flying) {
		var player = PlayerSrvc.getOrAddPlayer(gwsMessage.read32());
		var item = SL_Item.getById(gwsMessage.read32());
		if (item) {
			item.flying = flying;
			item.move(gwsMessage.read8(), gwsMessage.read8(), gwsMessage.read8());
			item.createMesh();
			if (!flying) {
				EffectSrvc.onThrowCollision(item);
			}
			return item;
		}
		else {
			console.error('Item not found.');
		}
	};

	CommandSrvc.xcmd_2026 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2026 EQUIP()');
		var slot = SL_Item.slotFromInt(gwsMessage.read8());
		var newItem = SL_Item.getById(gwsMessage.read32());
		var hand = SL_Item.getById(gwsMessage.read32());
		newItem.restoreCursor();
		SL_PLAYER.handItem(hand);
		SL_PLAYER.equip(newItem, slot);
	};

	

});
