'use strict';
angular.module('gwf4')
.service('BabylonSrvc', function() {
	
	var BabylonSrvc = this;
	
	BabylonSrvc.M = 1;
	
	BabylonSrvc.DIRECTIONS = ['N','E','S','W'];
	BabylonSrvc.DIR_V = {'N': [ 0, -1],'S': [ 0, 1],'E': [ 1, 0],'W': [-1, 0]};
	BabylonSrvc.DIR = 'N';
	BabylonSrvc.POS = new BABYLON.Vector3(0, 0, 0);
	BabylonSrvc.TARGET = BABYLON.Vector3.Zero();

	BabylonSrvc.init = function() {
		if (!BabylonSrvc.CANVAS) {
			BabylonSrvc.CANVAS = document.getElementById('game-canvas');
			BabylonSrvc.$CANVAS = $(BabylonSrvc.CANVAS);
			BabylonSrvc.ENGINE = new BABYLON.Engine(BabylonSrvc.CANVAS, true);
			BabylonSrvc.SCENE = new BABYLON.Scene(BabylonSrvc.ENGINE);
			BabylonSrvc.CAMERA = new BABYLON.FreeCamera("camera1", BabylonSrvc.POS, BabylonSrvc.SCENE);
			BabylonSrvc.CAMERA.setTarget(BabylonSrvc.TARGET);
			BabylonSrvc.CAMERA.attachControl(BabylonSrvc.CANVAS, false);
			BabylonSrvc.LIGHT = new BABYLON.PointLight("light", BabylonSrvc.POS, BabylonSrvc.SCENE);
			BabylonSrvc.ENGINE.runRenderLoop(function () {
				BabylonSrvc.SCENE.render();
			});
		}
	};
	
	// Move //
	BabylonSrvc.rotatedDir = function(by) {
		return BabylonSrvc.DIRECTIONS[(BabylonSrvc.DIRECTIONS.indexOf(BabylonSrvc.DIR) + by + 4) % 4];
	};

	BabylonSrvc.setCamera = function(x, y, z) {
		var pos1 = BabylonSrvc.CAMERA.position,
			pos2 = BabylonSrvc.LIGHT.position;
		var SQ = SL_Tile.SQ,
			sq = SL_Tile.sq;
		pos1.x = pos2.x = x * SQ;
		pos1.y = pos2.y = y * sq;
		pos1.z = pos2.z = z * SQ;
		BabylonSrvc.rotated();
	};

	BabylonSrvc.rotated = function() {
		var v = BabylonSrvc.DIR_V[BabylonSrvc.DIR];
		var t = BabylonSrvc.TARGET;
		var p = BabylonSrvc.CAMERA.position;
		var SQ = SL_Tile.SQ;
		t.x = p.x + v[0] * SQ;
		t.y = p.y;
		t.z = p.z + v[1] * SQ;
		BabylonSrvc.CAMERA.setTarget(t);
	};
	
	
	BabylonSrvc.initPlayer = function(player) {
		console.log('BabylonSrvc.initPlayer', player);
        var sphere = BABYLON.Mesh.CreateSphere("player"+player.id, 12, 5.5, BabylonSrvc.SCENE);
        sphere.position = player.position;
	};
	
	BabylonSrvc.addItem = function(item) {
		console.log('BabylonSrvc.addItem()', item);
		if (!item.mesh) {
			var SQ = SL_Tile.SQ,
				sq = SL_Tile.sq;
	        item.mesh = BABYLON.Mesh.CreateSphere("item"+item.id, 3, 5.0, BabylonSrvc.SCENE);
	        item.mesh.position = new BABYLON.Vector3(item.x*SQ, item.z*sq-item.flyZ(), item.y*SQ); 
		}
	};


	return BabylonSrvc;

});
