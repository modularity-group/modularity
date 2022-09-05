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

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Padaliyajay\PHPAutoprefixer\Autoprefixer;

class Modularity {

  public function __construct() {
    $this->loader();
    $this->updater();
    $this->adminbar();
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
    $name = basename($module);
    $directory = basename(dirname($module)) != "submodules" ? dirname($module) : dirname(dirname($module));
    $this->loadPHP("$module/$name.php");
    $this->enqueueFrontendJS("$module/$name.js");
    $this->enqueueBackendJS("$module/$name.editor.js");
    $this->compileSCSS("$module/$name.scss");
    $this->compileSCSS("$module/$name.editor.scss");
    $this->enqueueFrontendCSS("$module/$name.css");
    $this->enqueueBackendCSS("$module/$name.editor.css");
  }

  private function loadPHP($modulePHP) {
    if (!file_exists($modulePHP)) return;
    try {
      require_once("$modulePHP");
    }
    catch (Exception $error) {
      add_action("admin_notices", function() {
        echo "<div class='notice notice-error'><p>Error in <b>$modulePHP</b>.</p></div>";
      });
    }
  }

  private function shouldCompile($fileDist) {
    return isset($_GET['c']) || isset($_GET['compile']) || !file_exists($fileDist);
  }

  // private function compileJS($moduleJS, $moduleDist) {
  //   if (shouldCompile($moduleDist)) {
  //     file_put_contents($moduleDist, file_get_contents($moduleJS), FILE_APPEND);
  //   }
  // }

  private function enqueueFrontendJS($moduleJS) {
    if (!file_exists($moduleJS)) return;
    add_action("wp_enqueue_scripts", function(){
      wp_enqueue_script(
        basename($moduleJS) . ".js",
        "/wp-content/" . explode("/wp-content/", $moduleJS)[1],
        array("wp-blocks"),
        filemtime($moduleJS),
        true
      );
    }, 900);
  }

  private function enqueueBackendJS($moduleJS) {
    if (!file_exists($moduleJS)) return;
    add_action("enqueue_block_editor_assets", function(){
      wp_enqueue_script(
        basename($moduleJS) . ".js",
        "/wp-content/" . explode("/wp-content/", $moduleJS)[1],
        array(),
        filemtime($moduleJS),
        true
      );
    }, 900);
  }

  private function compileSCSS($moduleSCSS) {
    if (!file_exists($moduleSCSS)) return;
    if (!class_exists("Compiler")) require_once dirname(__FILE__) . '/vendor/autoload.php';
    $compiler = new Compiler();
    $compiler->setOutputStyle(OutputStyle::COMPRESSED);
    // $compiler->addImportPath( MODULES_DIR."/$basename/" );
    $scss = trim(@file_get_contents($moduleSCSS));
    if (!$scss) return;
    try {
      $css = $compiler->compileString($scss)->getCss();
      $this->saveSCSS($moduleSCSS, $css);
      if (strpos($scss, 'generate_editor_styles=true')) {
        // $cssEditor = $compiler->compileString('.editor-styles-wrapper .is-root-container {'.$scss.'}')->getCss();
        // $this->saveSCSS($moduleSCSS, $cssEditor);
      }
    }
    catch (Exception $e) {
      echo "Compiler error in $scss:<br>" . $e->getMessage();
    }
  }

  private function saveSCSS($moduleSCSS, $css) {
    $css = str_replace('@charset "UTF-8";', '', $css);
    $autoprefixer = new Autoprefixer($css);
    $css = $autoprefixer->compile();
    return file_put_contents(str_replace(".scss", ".css", $moduleSCSS), $css);
  }

  private function enqueueFrontendCSS($moduleCSS) {
    if (!file_exists($moduleCSS)) return;
    wp_enqueue_style(
      basename($moduleCSS) . ".css",
      "/wp-content/" . explode("/wp-content/", $moduleCSS)[1],
      array(),
      filemtime($moduleCSS),
      'all'
    );
  }

  private function enqueueBackendCSS($moduleCSS) {
    if (!file_exists($moduleCSS)) return;
    add_action("enqueue_block_editor_assets", function(){
      wp_enqueue_style(
        basename($moduleCSS) . ".css",
        "/wp-content/" . explode("/wp-content/", $moduleCSS)[1],
        array(),
        filemtime($moduleCSS),
        'all'
      );
    }, 900);
  }

  private function enqueueStylesheets() {
    add_action('enqueue_block_editor_assets', function(){
      $this->enqueueStylesheet();
    }, 20);
    add_action('admin_enqueue_scripts', function(){
      $this->enqueueStylesheet();
    }, 20);
    add_action('wp_enqueue_scripts', function(){
      $this->enqueueStylesheet();
    }, 20);
  }

  private function enqueueStylesheet() {
    wp_enqueue_style(
      'theme-styleshet',
      get_stylesheet_directory_uri() . '/style.css',
      array('theme-editor-styles'),
      filemtime( get_stylesheet_directory() . '/style.css' ),
      'all'
    );
  }

  private function loader() {
    foreach ($this->modules() as $module) {
      $this->load($module);
    }
  }

  private function updater() {
    // add_action('admin_init', function() {
    //   include_once "modularity.update.php";
    //   if (class_exists('modularityUpdateChecker')) {
    //     new modularityUpdateChecker();
    //   }
    // });
  }

  private function adminbar() {
    add_action('admin_bar_menu', function($wp_admin_bar) {
      if (current_user_can('administrator')) {
        $wp_admin_bar->add_node(
          array(
            'id' => 'modularity-compile',
            'title' => 'Compile Modules',
            'href' => home_url("?compile")
            'meta' => array(
              'class' => 'modularity-compile',
              'title' => 'Compile Modules'
            )
          )
        );
      }
    }, 999);
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
