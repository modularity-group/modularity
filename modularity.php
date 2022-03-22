<?php defined("ABSPATH") or die;
/*
Plugin Name: Modularity
Plugin URI:  https://github.com/modularity-group/modularity
Description: Modular wordpress development
Version:     4.0.7
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
    elseif (substr($moduleNameOrSlug, 0, 21) === "modularity-group/pro/") {
      $moduleUrl = "https://pro.modularity.group/releases/".substr($moduleNameOrSlug, 21)."/{$moduleUrlOrVersion}.zip?{$this->license()}";

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

  private function license() {
    return get_transient("modularity_license_key");
  }

  private function licenseValid() {
    $license = get_transient("modularity_license_key");
    if ($license && strlen($license) === 8) {
      return true;
    }
    return false;
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
      if (isset($_POST["modularity_reload"])) {
        wp_redirect("/wp-admin/admin.php?page=modularity");
        exit;
      }
      if (!isset($_POST["modularity_modules_delete"]) && !isset($_GET["force-delete"])) return;
      if (!is_user_logged_in()) return;
      if (!current_user_can("administrator")) return;
      if (is_dir(MODULES_DIR)) {
        $this->deleteDirectoryReccursively(MODULES_DIR);
        wp_redirect("/wp-admin/admin.php?page=modularity&modules_deleted");
        exit;
      }
    });

    add_action('wp_ajax_delete_module', function(){
      if (!isset($_POST["name"])) return;
      if (!current_user_can("administrator")) return;
      $module = htmlspecialchars(strip_tags($_POST['name']));
      if (is_dir(MODULES_DIR . "/$module")) {
        $this->deleteDirectoryReccursively(MODULES_DIR . "/$module");
        die('true');
      }
      die('false');
    });
  }

  private function modules() {
    return array_merge(
      $this->modulesPlugin(),
      $this->subModulesPlugin(),
      $this->modulesTheme(),
      $this->subModulesTheme()
    );
  }

  private function modulesPlugin($pro=false) {
    return $this->validModules(glob(MODULES_DIR."/*"), $pro);
  }

  private function subModulesPlugin($pro=false) {
    return $this->validModules(glob(MODULES_DIR."/[!_]*/modules/*"), $pro);
  }

  private function modulesTheme() {
    $folder = is_dir(get_stylesheet_directory()."/modules") ? "/modules/*" : "/*";
    return $this->validModules(glob(get_stylesheet_directory().$folder));
  }

  private function subModulesTheme() {
    $folder = is_dir(get_stylesheet_directory()."/modules") ? "/modules/[!_]*/modules/*" : "/[!_]*/modules/*";
    return $this->validModules(glob(get_stylesheet_directory().$folder));
  }

  private function freeModulesAvailable() {
    return [
      "core-module-style-loader",
      "core-module-script-loader",
      "core-module-style-variables",
      "config-css-reset",
      "config-block-editor",
      "config-site-template",
      "config-site-layout",
      "config-force-login",
      "config-advanced-user-roles",
      "config-advanced-block-editor",
      "config-wp-cleanup",
      "config-disable-comments",
      "config-library-jquery",
      "config-library-slick",
      "feature-responsive-header",
      "feature-modal-page",
      "feature-consent-dialog"
    ];
  }

  private function proModulesAvailable() {
    return [
      "feature-everything-slider",
      "feature-acf-edit-modal",
      "feature-projects-management"
    ];
  }

  private function validModules($candidates, $pro=false) {
    $modules = array();
    foreach ($this->moduleTypes as $prefix) {
      foreach ($candidates as $module) {
        if (
          substr(basename($module), 0, strlen($prefix)) === $prefix &&
          (!$pro || in_array(basename($module), $this->get_available_pro_modules()))
        ) {
          array_push($modules, $module);
        }
      }
    }
    return $modules;
  }

  private function admin() {
    add_action('admin_menu', function() {
      add_menu_page("Modules", "Modules", "manage_options", "modularity", function() {
        $INSTALLED_PRO_MODULES = $this->get_plugin_modules(true);
        $AVAILABLE_PRO_MODULES = $this->get_available_pro_modules();
        include_once "modularity.template.php";
      }, 'dashicons-rest-api', 61);
    });

    add_filter('plugin_action_links_'.plugin_basename(__FILE__), function($links){
      $links[] = '<a href="'.admin_url('admin.php?page=modularity').'">' . __('Settings') . '</a>';
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

  public function is_license_valid() {
    return $this->licenseValid();
  }

  public function get_modules_json() {
    return file_get_contents($this->modulesList);
  }

  public function get_all_modules() {
    return $this->modules();
  }

  public function get_plugin_modules($pro=false) {
    return $this->modulesPlugin($pro);
  }

  public function get_theme_modules() {
    return $this->modulesTheme();
  }

  public function get_available_free_modules() {
    return $this->freeModulesAvailable();
  }

  public function get_available_pro_modules() {
    return $this->proModulesAvailable();
  }

  public function get_custom_modules($endpoint) {
    return json_decode(file_get_contents($endpoint), true);
  }

  public function get_module_readme($moduleNameOrPath) {
    $readme = MODULES_DIR . "/" . basename($moduleNameOrPath) . "/readme.md";
    if (!file_exists($readme)) $readme = get_stylesheet_directory()."/" . basename($moduleNameOrPath) . "/readme.md";
    if (!file_exists($readme)) $readme = get_stylesheet_directory()."/modules/" . basename($moduleNameOrPath) . "/readme.md";
    return file_exists($readme) ? esc_attr(file_get_contents($readme)) : "";
  }

  public function get_module_version($moduleNameOrPath) {
    $readme = $this->get_module_readme($moduleNameOrPath);
    preg_match_all("/Version:\s([0-9]+.[0-9]+.[0-9]+)/", $readme, $_version);
    $version = $_version ? $_version[1][0] : false;

    if ($version) {
      $latest = $this->get_set_module_latest_tag(basename($moduleNameOrPath));
      if ($latest && str_replace("v","",$latest) > str_replace("v","",$version)) {
        return "v$version <strong class='newversion'>$latest</strong>";
      }
      return "v$version";
    }
    return "";
  }

  public function get_set_module_latest_tag($moduleSlug) {
    if (get_transient("module_" . $moduleSlug . "_latest_tag")) {
      return get_transient("module_" . $moduleSlug . "_latest_tag");
    }
    $curl = curl_init();
    curl_setopt($curl,CURLOPT_URL, "https://api.github.com/repos/modularity-group/$moduleSlug/git/refs/tags");
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Modularity");
    $info = curl_exec($curl);
    curl_close($curl);

    $tags = json_decode($info);
    if (!is_array($tags)) return;
    $latest = basename(array_reverse($tags)[0]->ref);
    set_transient("module_" . $moduleSlug . "_latest_tag", $latest, 1800);
    return $latest;
  }

}

$Modularity = new Modularity();
