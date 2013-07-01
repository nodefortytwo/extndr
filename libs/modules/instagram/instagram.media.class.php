<?php
class InstagramMedia extends MongoBase{
	protected $collection = 'instagramMedia';

	public function load_from_object($obj){
		$this->_id = $obj->id;
		$this['_id'] = $obj->id;
		unset($obj->id);
		foreach($obj as $key=>$val){
			$this[$key] = $val;
		}
		$this->save();
		$this->load_from_id();
	}

	public function to_gd($size = 306){
		$sizes = array('images.thumbnail.url' => 150, 'images.low_resolution.url' => 306, 'images.standard_resolution.url' => 612);
		$size_key = 'images.standard_resolution.url';
		//150 or 306 might be better.
		foreach($sizes as $key=>$s){
			if($s > $size){
				$size_key = $key;
			}
		}

		$im = imagecreatefromjpeg($this[$size_key]);
		return $im;
	}
}

class InstagramMediaCollection extends Collection{
	protected $collection = 'instagramMedia', $class_name = 'InstagramMedia';


	function render_gallery($style = 'gallery', $args = array()){

		$html = '<div class="row-fluid">';
		$c = 0;
		$t = 0;
		$w = 2;
		$cols = 12/$w;
		$rows = 5;

		foreach($this as $media){
			$c++;
			$t++;
			//var_dump($media['images.low_resolution.url']);
			$html .= '<div class="span2"><img src="' . $media['images.low_resolution.url'] . '" class="img-polaroid" width="100%" style="margin-top:10px;"/></div>';
			//die();
			if($t == ($cols * $rows)){
				break;
			}
			if($c == $cols){
				$c = 0;
				$html .= '</div><div class="row-fluid">';
			}
		}

		$html .= '</div>';
		return $html;
	}


	function render_image($args = array()){
		$args = $args['1'];
		if(!isset($args['type'])){
			throw new exception('Specify image type when rendering image');
		}

		$type = $args['type'];

		$instagram_sizes = array('images.thumbnail.url' => 150, 'images.low_resolution.url' => 306, 'images.standard_resolution.url' => 612);

		$target_size = $type['width'] / $type['cols'];

		$factors = common_factors($type['width'], $type['height']);

		if(count($factors) > 1){
			//we have some common factors (other than 1 of course)
			//find the nearest one to our target size
			$dif = 1000000;//stupid i know
			$match_id = 0;
			foreach($factors as $key => $f){
				$d = abs($f - $target_size);
				if($d < $dif){
					$match_id = $key;
					$dif = $d;
				}
			}
			$size = ceil($factors[$match_id] / 2);
		}

		if ($size < 100){
			$size = ceil($target_size);
		}

		$dif = 100000;
		foreach($instagram_sizes as $key => $f){
			$d = abs($f - $target_size);
			if($d < $dif){
				$instagram_type = $key;
				$dif = $d;
			}
		}

		$cols = ceil($type['width'] / $size);
		$rows = ceil($type['height'] / $size);
		$images_required = $cols * $rows;


		//yay we have enough images! Load them into memory
		$imgs = array();
		$c = 0;
		foreach($this as $image){
			if($c > $images_required){
				break;
			}
			$imgs[] = array($image['_id'], $image[$instagram_type]);
			$c++;
		}

		$id = md5(serialize($imgs));
		$path = config('UPLOAD_PATH') . '/' . $id . '.png';

		//loop through every col/row
		$map = array();
		$iid = 0;
		$im = imagecreatetruecolor($type['width'], $type['height']);
		for ($r = 0; $r < $rows; $r++) {
			for ($c = 0; $c < $cols; $c++) {
				$img = $imgs[$iid];
				$iid++;
				$cur_img = imagecreatefromjpeg($img[1]);
				$new_size = $size;
				$x = $new_size * $c;
				$y = $new_size * $r;
				$map[] = array($x, $y, $x + $new_size, $y + $new_size, $image);
				imagecopyresampled($im, $cur_img, $x, $y, 0, 0, $new_size, $new_size, imagesx($cur_img), imagesy($cur_img));
			}
		}
		imagepng($im, $path, 0);
		imagedestroy($im);
		return $id;

	}

}