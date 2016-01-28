<?php
final class Image {
  private $file;
  private $image;
  private $info;
	private $class;
  /**
   * @fn function __construct($file)
   * @brief 
   * @file image.php
   * 
   * @param file                
   * @return		
   */
  public function __construct() {
  	$this->file = null;
  	$this->image = null;
  	$this->info = null;
  }

  public function loadLib($class) {
    if (is_array($class))
      foreach ($class AS $key => $value)
	$this->$key = $value;
  }

  /**
   * @fn function __get($key)
   * @brief 
   * @file image.php
   * 
   * @param key         
   * @return		
   */
  public function __get($key)
  {
    return (isset($this->class[$key])) ? $this->class[$key] : NULL;
  }

  /**
   * @fn function __set($key, $val)
   * @brief 
   * @file image.php
   * 
   * @param key         
   * @param val         
   * @return		
   */
  public function __set($key, $val)
  {
    $this->class[$key] = $val;
  }

  
  public function	setImage($file)	{
	if (file_exists($file)) {
      $this->file = $file;
	    
      $info = getimagesize($file);
	    
      $this->info = array(
			  'width'  => $info[0],
			  'height' => $info[1],
			  'bits'   => $info['bits'],
			  'mime'   => $info['mime']
			  );
        	
      $this->image = $this->create($file);
    } else {
      exit('Error: Could not load image ' . $file . '!');
    }
	}
  /**
   * @fn function create($image)
   * @brief 
   * @file image.php
   * 
   * @param image               
   * @return		
   */
  private function create($image) {
    $mime = $this->info['mime'];
		
    if ($mime == 'image/gif') {
      return imagecreatefromgif($image);
    } elseif ($mime == 'image/png') {
      return imagecreatefrompng($image);
    } elseif ($mime == 'image/jpeg') {
      return imagecreatefromjpeg($image);
    }
  }	
	
  /**
   * @fn function save($file, $quality = 100)
   * @brief 
   * @file image.php
   * 
   * @param file                
   * @param quality             
   * @return		
   */
  public function save($file, $quality = 100) {
    $info = pathinfo($file);
    $extension = $info['extension'];
   
    if ($extension == ('jpeg' || 'jpg')) {
      imagejpeg($this->image, $file, $quality);
    } elseif($extension == 'png') {
      imagepng($this->image, $file, 0);
    } elseif($extension == 'gif') {
      imagegif($this->image, $file);
    }		   
    imagedestroy($this->image);
  }	    
	
  /**
   * @fn function resize($width = 0, $height = 0)
   * @brief 
   * @file image.php
   * 
   * @param width               
   * @param height              
   * @return		
   */
  public function resize($width = 0, $height = 0) {
    if (!$this->info['width'] || !$this->info['height']) {
      return;
    }

    $xpos = 0;
    $ypos = 0;

    $scale = min($width / $this->info['width'], $height / $this->info['height']);
		
    if ($scale == 1) {
      return;
    }
		
    $new_width = (int)($this->info['width'] * $scale);
    $new_height = (int)($this->info['height'] * $scale);			
    $xpos = (int)(($width - $new_width) / 2);
    $ypos = (int)(($height - $new_height) / 2);
        		        
    $image_old = $this->image;
    $this->image = imagecreatetruecolor($width, $height);
			
    $background = imagecolorallocate($this->image, 255, 255, 255);
    imagefilledrectangle($this->image, 0, 0, $width, $height, $background);
	
    imagecopyresampled($this->image, $image_old, $xpos, $ypos, 0, 0, $new_width, $new_height, $this->info['width'], $this->info['height']);
    imagedestroy($image_old);
           
    $this->info['width']  = $width;
    $this->info['height'] = $height;
  }
    
  /**
   * @fn function watermark($file, $position = 'bottomright')
   * @brief 
   * @file image.php
   * 
   * @param file                
   * @param position            
   * @return		
   */
  public function watermark($file, $position = 'bottomright') {
    $watermark = $this->create($file);
        
    $watermark_width = imagesx($watermark);
    $watermark_height = imagesy($watermark);
        
    switch($position) {
    case 'topleft':
      $watermark_pos_x = 0;
      $watermark_pos_y = 0;
      break;
    case 'topright':
      $watermark_pos_x = $this->info['width'] - $watermark_width;
      $watermark_pos_y = 0;
      break;
    case 'bottomleft':
      $watermark_pos_x = 0;
      $watermark_pos_y = $this->info['height'] - $watermark_height;
      break;
    case 'bottomright':
      $watermark_pos_x = $this->info['width'] - $watermark_width;
      $watermark_pos_y = $this->info['height'] - $watermark_height;
      break;
    }
        
    imagecopy($this->image, $watermark, $watermark_pos_x, $watermark_pos_y, 0, 0, 120, 40);
        
    imagedestroy($watermark);
  }
    
  /**
   * @fn function crop($top_x, $top_y, $bottom_x, $bottom_y)
   * @brief 
   * @file image.php
   * 
   * @param top_x               
   * @param top_y               
   * @param bottom_x            
   * @param bottom_y    	
   * @return		
   */
  public function crop($top_x, $top_y, $bottom_x, $bottom_y) {
    $image_old = $this->image;
    $this->image = imagecreatetruecolor($bottom_x - $top_x, $bottom_y - $top_y);
        
    imagecopy($this->image, $image_old, 0, 0, $top_x, $top_y, $this->info['width'], $this->info['height']);
    imagedestroy($image_old);
        
    $this->info['width'] = $bottom_x - $top_x;
    $this->info['height'] = $bottom_y - $top_y;
  }
    
  /**
   * @fn function rotate($degree, $color = 'FFFFFF')
   * @brief 
   * @file image.php
   * 
   * @param degree              
   * @param color               
   * @return		
   */
  public function rotate($degree, $color = 'FFFFFF') {
    $rgb = $this->html2rgb($color);
		
    $this->image = imagerotate($this->image, $degree, imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));
        
    $this->info['width'] = imagesx($this->image);
    $this->info['height'] = imagesy($this->image);
  }
	    
  /**
   * @fn function filter($filter)
   * @brief 
   * @file image.php
   * 
   * @param filter              
   * @return		
   */
  private function filter($filter) {
    imagefilter($this->image, $filter);
  }
            
  /**
   * @fn function text($text, $x = 0, $y = 0, $size = 5, $color = '000000')
   * @brief 
   * @file image.php
   * 
   * @param text                
   * @param x           
   * @param y           
   * @param size                
   * @param color		
   * @return		
   */
  private function text($text, $x = 0, $y = 0, $size = 5, $color = '000000') {
    $rgb = $this->html2rgb($color);
        
    imagestring($this->image, $size, $x, $y, $text, imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));
  }
    
  /**
   * @fn function merge($file, $x = 0, $y = 0, $opacity = 100)
   * @brief 
   * @file image.php
   * 
   * @param file                
   * @param x           
   * @param y           
   * @param opacity     	
   * @return		
   */
  private function merge($file, $x = 0, $y = 0, $opacity = 100) {
    $merge = $this->create($file);

    $merge_width = imagesx($image);
    $merge_height = imagesy($image);
		        
    imagecopymerge($this->image, $merge, $x, $y, 0, 0, $merge_width, $merge_height, $opacity);
  }
			
  /**
   * @fn function html2rgb($color)
   * @brief 
   * @file image.php
   * 
   * @param color               
   * @return		
   */
  private function html2rgb($color) {
    if ($color[0] == '#') {
      $color = substr($color, 1);
    }
		
    if (strlen($color) == 6) {
      list($r, $g, $b) = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);   
    } elseif (strlen($color) == 3) {
      list($r, $g, $b) = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);    
    } else {
      return FALSE;
    }
		
    $r = hexdec($r); 
    $g = hexdec($g); 
    $b = hexdec($b);    
		
    return array($r, $g, $b);
  }	
  }
?>