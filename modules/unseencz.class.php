<?php

/**
 * Class for downloading unseen comix from http://unseen.bloguje.cz
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author Frantisek Tuma <tumaf@seznam.cz>, @Feainne
 * @package daily-comics-to-kindle
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @origin https://github.com/Georgo/daily-comics-to-kindle
 */

class unseencz {
	public $idref = 'unseencz';
	public $title = null;
	public $manifest = array();

	public function generate($dir) {
		if(file_exists($dir.'/'.$this->idref.'.html')) {
			$this->title = file_get_contents($dir.'/'.$this->idref.'.title');
			$this->manifest = array(array(
				'id' => 1,
				'filename' => $this->idref.'.gif',
				'content-type' => 'image/gif'
			));
			return true;
		}

		/** Download RSS */
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, 'http://unseen.bloguje.cz/tema-6-life-unseen.xml');
		curl_setopt($c, CURLOPT_HEADER, false);
		curl_setopt($c, CURLOPT_USERAGENT, 'KindleGenerator/1.0');
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_TIMEOUT, 15);
		$html = curl_exec($c);
		curl_close($c);
		unset($c);
		
		if($html === false) {
			return false;
		}

		/** Parse <item> */
		if(!preg_match('/<item>(.*)<\/item>/is', $html, $items)) {
			return false;
		}	

		$item = $items[0];
                $unseenurl = '';
                		
		if(preg_match('/<link>(.*)<\/link>/i', $item, $tmp)) {
			$unseenurl = $tmp[1];
		}
                                
		/** Download article with comics */
                $c = curl_init();
		curl_setopt($c, CURLOPT_URL, $unseenurl);
		curl_setopt($c, CURLOPT_HEADER, false);
		curl_setopt($c, CURLOPT_USERAGENT, 'KindleGenerator/1.0');
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_TIMEOUT, 15);
		$html = curl_exec($c);
		curl_close($c);
		unset($c);    
		/** Grab cartoon image */
                
		if(preg_match('/<img src="(http:\/\/unseen.appspot.com\/strip\/[^"]+)">/i', $html, $item)) {
                    
			$this->title = 'Unseen (cs)';
			unset($html);	
			$imgurl = $item[1];
			
			if($imgurl == '' || (file_exists('last/'.$this->idref) && file_get_contents('last/'.$this->idref) == $imgurl)) {
				echo $this->idref.' is old'."\n";
				return false;
			}
			file_put_contents('last/'.$this->idref, $imgurl);


			$c = curl_init();
			curl_setopt($c, CURLOPT_URL, $imgurl);
			curl_setopt($c, CURLOPT_HEADER, false);
			curl_setopt($c, CURLOPT_USERAGENT, 'KindleGenerator/1.0');
			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($c, CURLOPT_TIMEOUT, 15);
			$img = curl_exec($c);
			curl_close($c);
			unset($c);
		
			if($img !== false) {
				file_put_contents($dir.'/'.$this->idref.'.png', $img);
                                
                                /** Convert PNG image to GIF */
				#exec('/usr/bin/convert -type grayscale '.$dir.'/'.$this->idref.'.png '.$dir.'/'.$this->idref.'.gif');

				$this->manifest = array(array(
					'id' => 1,
					#'filename' => $this->idref.'.gif',
					#'content-type' => 'image/gif'
					'filename' => $this->idref.'.png',
					'content-type' => 'image/png'
				));

				$code = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>'.$this->title.'</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
<link rel="stylesheet" href="comics.css" type="text/css" />
<body>
<div>
</head>
<h2>'.$this->title.'</h2>
<p class="centered"><img src="'.$this->idref.'.png" /></p>
</div>
<mbp:pagebreak/>
</body>
</html>
';
				file_put_contents($dir.'/'.$this->idref.'.html', $code);
				file_put_contents($dir.'/'.$this->idref.'.title', $this->title);
				return true;
			}
			return false;
		}
                return false;
        }

}


?>
