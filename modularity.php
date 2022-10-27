<?php defined("ABSPATH") or die;
/*
Plugin Name: Modularity
Plugin URI:  https://github.com/modularity5-group/modularity
Description: Modular Theme Development-System for WordPress
Version:     5.0.2
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

    public function data() {
      return get_plugin_data(__FILE__);
    }
  }

  $Modularity = new Modularity();
}
