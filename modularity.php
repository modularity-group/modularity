<?php defined("ABSPATH") or die;
/*
Plugin Name: Modularity
Plugin URI:  https://github.com/modularity-group/modularity
Description: Modular wordpress development
Version:     4.0.0
Author:      Modularity Group
Author URI:  https://modularity.group
Text Domain: modularity
*/

define("MODULES_DIR", WP_CONTENT_DIR . "/modules");
define("MODULES_PATH", "/wp-content/modules");

class Modularity {

  private $modulesList;
  private $moduleTypes;

  public function __construct() {
    $this->modulesList = get_stylesheet_directory() . "/modules.json";
    $this->moduleTypes = array("core", "config", "wp-block", "feature");
    $this->admin();
    $this->installer();
    $this->loader();
    $this->deleter();
    $this->updater();
  }

  private function installer() {
    if (!is_dir(MODULES_DIR)) mkdir(MODULES_DIR);
    if (!file_exists($this->modulesList)) return;
    $modules = json_decode($this->get_modules_json(), true);

    foreach ($modules as $module) {
      $moduleNameOrSlug = strip_tags(key($module));
      $moduleUrlOrVersion = strip_tags($module[$moduleNameOrSlug]);

      if (!file_exists(MODULES_DIR . "/" . basename($moduleNameOrSlug))) {
        $this->install($moduleNameOrSlug, $moduleUrlOrVersion);
      }
    }
  }

  private function install($moduleNameOrSlug, $moduleUrlOrVersion) {
    if (empty($moduleNameOrSlug)) return;
    if (empty($moduleUrlOrVersion)) return;
    $moduleUrl = $moduleUrlOrVersion;

    if ($moduleUrlOrVersion === 'master') {
      $moduleUrl = "https://github.com/$moduleNameOrSlug/archive/refs/heads/master.zip";
    }
    elseif (substr($moduleUrlOrVersion, 0, 8) !== "https://") {
      $moduleUrl = "https://github.com/$moduleNameOrSlug/archive/refs/tags/v{$moduleUrlOrVersion}.zip";
    }

    $archive = @file_get_contents($moduleUrl);
    if (!$archive) {
      add_action("admin_notices", function() use ($moduleNameOrSlug) {
        echo "<div class='notice notice-error'><p>Could not install module <b>$moduleNameOrSlug</b>.</p></div>";
      });
      return;
    }
    $archiveName = basename($moduleUrl);
    $archivePath = MODULES_DIR . "/$archiveName";
    if (file_exists($archivePath)) return;

    if (file_put_contents($archivePath, $archive)) {
      $zip = new ZipArchive;
      if ($zip->open($archivePath) === true) {
        $archiveEntry = $zip->getNameIndex(0);
        $zip->extractTo(MODULES_DIR);
        $zip->close();
        unlink($archivePath);

        if (is_dir(MODULES_DIR . "/$archiveEntry")) {
          $moduleNameClean = false;

          if (preg_match("/-(\d+.)?(\d+.)?(\d+\/)/", $archiveEntry)) {
            $moduleNameClean = preg_replace("/-(\d+.)?(\d+.)?(\d+)/", "", $archiveEntry);
          }
          elseif (preg_match("/-master\//", $archiveEntry)) {
            $moduleNameClean = preg_replace("/-master\//", "/", $archiveEntry);
          }
          if ($moduleNameClean) {
            $renameModule = rename(MODULES_DIR . "/$archiveEntry", MODULES_DIR . "/$moduleNameClean");
          }
        }
      }
    }
  }

  private function loader() {
    foreach ($this->modules() as $module) {
      $this->load($module);
    }
  }

  private function load($module) {
    $php = $module . "/" . basename($module) . ".php";
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

  private function deleter() {
    add_action('admin_init', function() {
      if (!isset($_POST["modularity_modules_delete"])) return;
      if (!is_user_logged_in()) return;
      if (!current_user_can("administrator")) return;
      if (is_dir(MODULES_DIR)) {
        $this->deleteDirectoryReccursively(MODULES_DIR);
        header("location: /wp-admin/themes.php?page=modularity&modules_deleted");
        exit;
      }
    });
  }

  private function modules() {
    return array_merge($this->modulesPlugin(), $this->modulesTheme());
  }

  private function modulesPlugin() {
    return $this->validModules(glob(MODULES_DIR."/*"));
  }

  private function modulesTheme() {
    return $this->validModules(glob(get_stylesheet_directory()."/*"));
  }

  private function modulesAvailable() {
    return [
      "core-module-style-loader",
      "core-module-script-loader",
      "core-module-style-variables",
      "config-site-template",
      "config-site-layout",
      "config-force-login",
      "config-advanced-user-roles",
      "config-wp-cleanup",
      "config-css-reset",
      "config-disable-comments",
      "config-block-editor",
      "config-library-jquery",
      "config-library-slick",
      "feature-responsive-header",
      "feature-modal-page",
      "feature-consent-dialog"
    ];
  }

  private function validModules($candidates) {
    $modules = array();
    foreach ($this->moduleTypes as $prefix) {
      foreach ($candidates as $module) {
        if (substr(basename($module), 0, strlen($prefix)) === $prefix) {
          array_push($modules, $module);
        }
      }
    }
    return $modules;
  }

  private function admin() {
    add_action('admin_menu', function() {
      add_submenu_page(
        "themes.php",
        "Modules",
        "Modules",
        "manage_options",
        "modularity",
        function() {
          include_once "modularity.template.php";
        },
        91
      );
    });

    add_filter('plugin_action_links_'.plugin_basename(__FILE__), function($links){
      $links[] = '<a href="'.admin_url('themes.php?page=modularity').'">' . __('Settings') . '</a>';
      return $links;
    });
  }

  private function deleteDirectoryReccursively($directory) {
    if (is_dir($directory)) {
      $objects = scandir($directory);

      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          $path = "$directory/$object";

          if (filetype($path) == "dir") {
            $this->deleteDirectoryReccursively($path);
          } else {
            unlink($path);
          }
        }
      }
      reset($objects);
      rmdir($directory);
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

  public function get_modules_json() {
    return file_get_contents($this->modulesList);
  }

  public function get_all_modules() {
    return $this->modules();
  }

  public function get_plugin_modules() {
    return $this->modulesPlugin();
  }

  public function get_theme_modules() {
    return $this->modulesTheme();
  }

  public function get_available_modules() {
    return $this->modulesAvailable();
  }

  public function get_custom_modules($endpoint) {
    return json_decode(file_get_contents($endpoint), true);
  }

}

$Modularity = new Modularity();
