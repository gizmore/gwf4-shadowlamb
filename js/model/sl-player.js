function SL_Player() {
	
	this.x = this.y = this.z = 0;
	
	this.move = function(x, y, z) { this.x = x; this.y = y; this.z = z; };
	
	return this;
}
