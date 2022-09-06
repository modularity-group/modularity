<?php defined("ABSPATH") or die;
/*
Plugin Name: Modularity
Plugin URI:  https://github.com/modularity-group/modularity5-plugin
Description: Modular WordPress theme development system
Version:     5.0.0.b1
Author:      Modularity Group
Author URI:  https://modularity.group
Text Domain: modularity
*/

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Padaliyajay\PHPAutoprefixer\Autoprefixer;

class Modularity {

  private $stylesheets;
  private $stylesheetsEditor;
  private $scripts;
  private $scriptsEditor;

  public function __construct() {
    $this->stylesheets = [get_stylesheet_directory() . '/style.css'];
    $this->stylesheetsEditor = [get_stylesheet_directory() . '/style.css'];
    $this->scripts = [];
    $this->scriptsEditor = [];
    $this->loader();
    $this->adminbar();

    add_action('wp_enqueue_scripts', array($this, 'frontendEnqueuer'), 20);
    add_action('enqueue_block_editor_assets', array($this, 'editorEnqueuer'), 20);
    add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'), 20);
    add_action('enqueue_block_editor_assets', array($this, 'enqueueScriptsEditor'), 20);
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
    if ($this->shouldCompile($module)) {
      $this->compileSCSS("$module/$name.scss");
      $this->compileSCSS("$module/$name.editor.scss");
    }
    $this->stylesheets[] = "$module/$name.css";
    $this->stylesheetsEditor[] = "$module/$name.editor.css";
    $this->scripts[] = "$module/$name.js";
    $this->scriptsEditor[] = "$module/$name.editor.js";
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

  private function compileSCSS($moduleSCSS) {
    if (!file_exists($moduleSCSS)) return;
    if (!class_exists("Compiler")) require_once dirname(__FILE__) . '/vendor/autoload.php';
    $compiler = new Compiler();
    $compiler->setOutputStyle(OutputStyle::COMPRESSED);
    $compiler->addImportPath(dirname($moduleSCSS));
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

  private function loader() {
    foreach ($this->modules() as $module) {
      $this->load($module);
    }
  }

  public function editorEnqueuer() {
    $this->enqueue($this->stylesheetsEditor);
  }

  public function frontendEnqueuer() {
    $this->enqueue($this->stylesheets);
  }

  public function enqueue($stylesheets=[]) {
    foreach ($stylesheets as $file) {
      if (file_exists($file)) {        
        wp_enqueue_style(basename($file, ".css"), $this->directoryToPath($file), [], filemtime($file), 'all');
      }
    }
  }

  public function enqueueScripts() {
    $this->enqueueScript($this->scripts);
  }

  public function enqueueScriptsEditor() {
    $this->enqueueScript($this->scriptsEditor);
  }

  private function enqueueScript($scripts=[]) {
    foreach ($scripts as $file) {
      if (file_exists($file)) {
        wp_enqueue_script(basename($file, ".js"), $this->directoryToPath($file), ["jquery"], filemtime($file), true);
      }
    }
  }

  private function directoryToPath($directory) {
    return "/wp-content/" . explode("/wp-content/", $directory)[1];
  }

  // private function updater() {
    // add_action('admin_init', function() {
    //   include_once "modularity.update.php";
    //   if (class_exists('modularityUpdateChecker')) {
    //     new modularityUpdateChecker();
    //   }
    // });
  // }

  private function adminbar() {
    add_action('admin_bar_menu', function($wp_admin_bar) {
      if (current_user_can('administrator')) {
        $wp_admin_bar->add_node(
          array(
            'id' => 'modularity-compile',
            'title' => 'Compile Modules',
            'href' => home_url("?compile"),
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
