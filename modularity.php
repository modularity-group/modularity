<?php defined("ABSPATH") or die;
/*
Plugin Name: Modularity
Plugin URI:  https://github.com/modularity-group/modularity
Description: Modular Theme Development-System for WordPress
Version:     5.0.3.beta1
Author:      Modularity Group
Author URI:  https://www.modularity.group
Text Domain: modularity
*/

if (!class_exists("Modularity")) {

  class Modularity {

    public function __construct() {
      require_once "includes/ModularityCore.php";
      require_once "includes/ModularityBase.php";
    }

    static function name() {
      return "Modularity Pro";
    }

    static function version() {
      return "1.0.0.beta6";
    }

    static function github() {
      return "modularity5-group/modularity-pro";
    }

    static function path() {
      return dirname(__FILE__);
    }
  }

  $Modularity = new Modularity();
}
