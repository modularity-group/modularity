<?php defined("ABSPATH") or die;
/*
Plugin Name: Modularity
Plugin URI:  https://github.com/modularity-group/modularity
Description: Modular Theme Development-System for WordPress
Version:     5.0.3
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
      return "Modularity";
    }

    static function version() {
      return "5.0.3";
    }

    static function github() {
      return "modularity-group/modularity";
    }

    static function path() {
      return dirname(__FILE__);
    }
  }

  $Modularity = new Modularity();
}
