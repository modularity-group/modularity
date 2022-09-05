<?php defined("ABSPATH") or die;

$themeModulesFolderUrl = is_dir(get_stylesheet_directory()."/modules") ? get_stylesheet_directory_uri()."/modules" : get_stylesheet_directory_uri();
$themeModulesFolderPath = is_dir(get_stylesheet_directory()."/modules") ? get_stylesheet_directory()."/modules" : get_stylesheet_directory();

if (is_dir(MODULES_DIR) && is_dir($themeModulesFolderPath)) {

  if(!defined("DIST_URL")){
    define("DIST_URL", $themeModulesFolderUrl);
  }
  if(!defined("DIST_PATH")){
    define("DIST_PATH", $themeModulesFolderPath);
  }
  $jsFileModules = MODULES_DIR . "/modules.js";
  $jsEditorFileModules = MODULES_DIR . "/modules.editor.js";
  $jsFileTheme = DIST_PATH . "/bundle.js";
  $jsEditorFileTheme = DIST_PATH . "/bundle.editor.js";

  if (isset($_GET['c']) || isset($_GET['compile']) || !file_exists($jsFileModules) || !file_exists($jsEditorFileModules) || !file_exists($jsFileTheme) || !file_exists($jsEditorFileTheme) ) {
    if (!is_dir(DIST_PATH)) {
      mkdir(DIST_PATH);
    }

    file_put_contents($jsFileModules, "");
    file_put_contents($jsEditorFileModules, "");
    file_put_contents($jsFileTheme, "");
    file_put_contents($jsEditorFileTheme, "");

    foreach (array("core", "config", "wp-block", "block", "feature") as $prefix) {
      foreach (glob(MODULES_DIR."/*") as $libraryModule) {
        $basename = basename($libraryModule);
        if (substr($basename, 0, strlen($prefix)) === $prefix) {
          $moduleJs = MODULES_DIR."/$basename/$basename.js";
          if (file_exists($moduleJs)) {
            file_put_contents(
              $jsFileModules,
              file_get_contents($moduleJs),
              FILE_APPEND
            );
          }
          $moduleEditorJs = MODULES_DIR."/$basename/$basename.editor.js";
          if (file_exists($moduleEditorJs)) {
            file_put_contents(
              $jsEditorFileModules,
              file_get_contents($moduleEditorJs),
              FILE_APPEND
            );
          }
        }
        // <SUBMODULES>
        foreach (glob(MODULES_DIR."/".$basename."/modules/*") as $librarySubModule) {
          $basenameSub = basename($librarySubModule);
          if (substr($basenameSub, 0, strlen($prefix)) === $prefix) {
            $moduleJs = MODULES_DIR."/$basename/modules/$basenameSub/$basenameSub.js";
            if (file_exists($moduleJs)) {
              file_put_contents(
                $jsFileModules,
                file_get_contents($moduleJs),
                FILE_APPEND
              );
            }
            $moduleEditorJs = MODULES_DIR."/$basename/modules/$basenameSub/$basenameSub.editor.js";
            if (file_exists($moduleEditorJs)) {
              file_put_contents(
                $jsEditorFileModules,
                file_get_contents($moduleEditorJs),
                FILE_APPEND
              );
            }
          }
        }
        // </SUBMODULES>
      }
      foreach (glob("{$themeModulesFolderPath}/*") as $module) {
        $basename = basename($module);
        if (substr($basename, 0, strlen($prefix)) === $prefix) {
          $moduleJs = "{$themeModulesFolderPath}/$basename/$basename.js";
          if (file_exists($moduleJs)) {
            file_put_contents(
              $jsFileTheme,
              file_get_contents($moduleJs),
              FILE_APPEND
            );
          }
          $moduleEditorJs = "{$themeModulesFolderPath}/$basename/$basename.editor.js";
          if (file_exists($moduleEditorJs)) {
            file_put_contents(
              $jsEditorFileTheme,
              file_get_contents($moduleEditorJs),
              FILE_APPEND
            );
          }
        }
        // <SUBMODULES>
        foreach (glob("{$themeModulesFolderPath}/$basename/modules/*") as $subModule) {
          $basenameSub = basename($subModule);
          if (substr($basenameSub, 0, strlen($prefix)) === $prefix) {
            $moduleJs = "{$themeModulesFolderPath}/$basename/modules/$basenameSub/$basenameSub.js";
            if (file_exists($moduleJs)) {
              file_put_contents(
                $jsFileModules,
                file_get_contents($moduleJs),
                FILE_APPEND
              );
            }
            $moduleEditorJs = "{$themeModulesFolderPath}/$basename/modules/$basenameSub/$basenameSub.editor.js";
            if (file_exists($moduleEditorJs)) {
              file_put_contents(
                $jsEditorFileModules,
                file_get_contents($moduleEditorJs),
                FILE_APPEND
              );
            }
          }
        }
        // </SUBMODULES>
      }
    }
  }

  add_action("wp_enqueue_scripts", function(){
    wp_enqueue_script(
      "core-module-script-loader-modules",
      MODULES_PATH . "/modules.js",
      //($this->moduleExists('config-jquery') ? array('jquery') : array()),
      array('jquery'),
      filemtime(MODULES_DIR . "/modules.js"),
      true
    );
    wp_enqueue_script(
      "core-module-script-loader-theme",
      DIST_URL . "/bundle.js",
      //($this->moduleExists('config-jquery') ? array('jquery') : array()),
      array('jquery','core-module-script-loader-modules'),
      filemtime(DIST_PATH . "/bundle.js"),
      true
    );
  }, 900);

  add_action("enqueue_block_editor_assets", function(){
    wp_enqueue_script(
      "core-module-script-loader-modules-editor",
      MODULES_PATH . "/modules.editor.js",
      //($this->moduleExists('config-jquery') ? array('jquery') : array()),
      array('jquery','wp-blocks'),
      filemtime(MODULES_DIR . "/modules.editor.js"),
      true
    );
    wp_enqueue_script(
      "core-module-script-loader-theme-editor",
      DIST_URL . "/bundle.editor.js",
      //($this->moduleExists('config-jquery') ? array('jquery') : array()),
      array('core-module-script-loader-modules-editor'),
      filemtime(DIST_PATH . "/bundle.editor.js"),
      true
    );
  }, 900);
}
