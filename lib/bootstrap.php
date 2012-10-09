<?php
  class Bootstrap {
	  
	  public static function set_charset($charset) {
		  echo '<meta charset="' . $charset . '">' . "\n";
	  }
	  
	  public static function set_title($title) {
		  echo '<title>' . $title . '</title>' . "\n";
	  }
	  
	  public static function make_meta($name, $content = '') {
		  echo '<meta name="' . $name . '" content="' . $content . '">' . "\n";
	  }
	  
	  public static function add_css($link) {
		  echo '<link href="' . $link . '" rel="stylesheet" type="text/css">' . "\n";
	  }
	  
	  public static function make_input($id, $label, $type, $placeholder = '') {
		  echo '<label for="' . $id . '">' . $label . '</label><input class="input-fluid input-large" id="' . $id . '" name="' . $id . '" type="' . $type . '" placeholder="' . $placeholder . '">' . "\n";
	  }
	  
	  public static function make_navbar_button($link, $label) {
		  echo '<li class=""><a href="' . $link . '">' . $label . '</a></li>' . "\n";
	  }
	  
	  public static function add_script($link, $type = '') {
		  echo '<script type="' . $type . '" src="' . $link . '"></script>' . "\n";
	  }
		  
  }
