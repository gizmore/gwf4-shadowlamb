<?php
final class Shadowlamb_Icon extends GWF_Method
{
	private $width = 64, $height = 64;
	
	private function fontPath()
	{
		return $this->module->getModuleFilePath('font/tiny.ttf');
	}
	
	public function execute()
	{
		$item = Common::getRequestString('name');
		$item = preg_replace('/[^a-z_0-9]/i', '', $item);
		$path = $this->module->getModuleFilePath('icon/item/'.$item.'.gif');
		
		if (GWF_File::isFile($path))
		{
			$this->existingIcon($path);
		}
		else
		{
			$this->generateIcon($item);
		}
		die(0);
	}
	
	private function existingIcon($path)
	{
		header('Content-type: image/gif');
		echo file_get_contents($path);
	}
	
	private function generateIcon($name)
	{
		$image = imagecreate($this->width, $this->height);
		$white = imagecolorallocate($image, 255, 255, 255);
		imagefill ($image, 0, 0, $white);
		$black = imagecolorallocate($image, 0, 0, 0);
		imagettftext($image, 12, 0, 2, 16, $black, $this->fontPath(), substr($name, 0, 7));
		imagettftext($image, 12, 0, 2, 32, $black, $this->fontPath(), substr($name, 7, 7));
		imagettftext($image, 12, 0, 2, 48, $black, $this->fontPath(), substr($name, 14, 7));
		imagettftext($image, 12, 0, 2, 62, $black, $this->fontPath(), substr($name, 21, 7));
		header('Content-Type: image/gif');
		echo imagegif($image);
		imagedestroy($image);
	}

}