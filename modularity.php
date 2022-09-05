<?php defined("ABSPATH") or die;
/*
Plugin Name: Modularity
Plugin URI:  https://github.com/modularity-group/modularity5-plugin
Description: Modular WordPress theme development system
Version:     5.0.x
Author:      Modularity Group
Author URI:  https://modularity.group
Text Domain: modularity
*/

// define("MODULES_PATH", "/wp-content/modules");

class Modularity {

  public function __construct() {
    $this->loader();
    $this->updater();
  }

  private function modules() {
    return array_merge(
      glob(dirname(__FILE__) . "/modules/[!_]*"),
      glob(dirname(__FILE__) . "/modules/[!_]*/submodules/[!_]*"),
      glob(WP_CONTENT_DIR . "/modules/[!_]*"),
      glob(WP_CONTENT_DIR . "/modules/[!_]*/submodules/[!_]*"),
      glob(get_stylesheet_directory() . "/modules/[!_]*"),
      glob(get_stylesheet_directory() . "/modules/[!_]*/modules/[!_]*")
    );
  }

  private function load($module) {
    $php = "$module/" . basename($module) . ".php";
    if (!file_exists($php)) return;
    try {
      require_once($php);
    }
    catch (Exception $error) {
      add_action("admin_notices", function() {
        echo "<div class='notice notice-error'><p>Error in <b>$php</b>.</p></div>";
      });
    }
  }

  private function loader() {
    foreach ($this->modules() as $module) {
      $this->load($module);
    }
  }

  private function updater() {
    add_action('admin_init', function() {
      include_once "modularity.update.php";
      if (class_exists('modularityUpdateChecker')) {
        new modularityUpdateChecker();
      }
    });
  }

  // private function freeModulesAvailable() {
  //   return [
  //     "core-module-style-loader",
  //     "core-module-script-loader",
  //     "core-module-style-variables",
  //     "config-css-reset",
  //     "config-block-editor",
  //     "config-site-template",
  //     "config-site-layout",
  //     "config-force-login",
  //     "config-advanced-user-roles",
  //     "config-advanced-block-editor",
  //     "config-wp-cleanup",
  //     "config-disable-comments",
  //     "config-library-jquery",
  //     "config-library-slick",
  //     "feature-responsive-header",
  //     "feature-modal-page",
  //     "feature-consent-dialog",
  //     "config-scroll-animations"
  //   ];
  // }

}

$Modularity = new Modularity();
