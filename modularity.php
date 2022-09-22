<?php defined("ABSPATH") or die;
/*
Plugin Name: Modularity
Plugin URI:  https://github.com/modularity-group/modularity5-plugin
Description: Modular WordPress theme development system
Version:     5.0.0.b5
Author:      Modularity Group
Author URI:  https://modularity.group
Text Domain: modularity
*/

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Padaliyajay\PHPAutoprefixer\Autoprefixer;

class Modularity {

  private $themeStyles;
  private $themeScripts;
  private $editorStyles;
  private $editorScripts;

  public function __construct() {
    $this->themeStyles = [];
    $this->themeScripts = [];
    $this->editorStyles = [];
    $this->editorScripts = [];
    $this->loader();
    $this->adminbar();
    $this->themeStyles[] = get_stylesheet_directory() . "/style.css";
    $this->editorStyles[] = get_stylesheet_directory() . "/style.css";

    add_action('wp_enqueue_scripts', [$this, 'enqueueThemeStyles'], 20);
    add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorStyles'], 20);
    add_action('wp_enqueue_scripts', [$this, 'enqueueThemeScripts'], 20);
    add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorScripts'], 20);
  }

  private function loader() {
    foreach ($this->modules() as $module) {
      $this->load($module);
    }
  }

  private function modules() {
    return array_merge(
      glob(WP_CONTENT_DIR . "/modules/[!_]*"),
      glob(WP_CONTENT_DIR . "/modules/[!_]*/submodules/[!_]*"),
      glob(get_stylesheet_directory() . "/modules/[!_]*"),
      glob(get_stylesheet_directory() . "/modules/[!_]*/submodules/[!_]*")
    );
  }

  private function load($module) {
    $name = basename($module);
    $this->loadPHP("$module/$name.php");
    if ($this->shouldCompile()) {
      $this->compileSCSS("$module/$name.scss");
      $this->compileSCSS("$module/$name.editor.scss");
    }
    $this->themeStyles[] = "$module/$name.css";
    $this->editorStyles[] = "$module/$name.editor.css";
    $this->themeScripts[] = "$module/$name.js";
    $this->editorScripts[] = "$module/$name.editor.js";
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

  private function shouldCompile() {
    return isset($_GET["c"]) || isset($_GET["compile"]); // || !file_exists($target);
  }

  private function compileSCSS($moduleSCSS) {
    if (!file_exists($moduleSCSS)) return;
    if (!class_exists("Compiler")) {
      require_once dirname(__FILE__) . "/vendor/autoload.php";
    }
    $compiler = new Compiler();
    $compiler->addImportPath(dirname($moduleSCSS));
    $scss = trim(@file_get_contents($moduleSCSS));
    if (!$scss) {
      unlink($this->sassToCssPath($moduleSCSS));
      return;
    }
    try {
      $css = $compiler->compileString($scss)->getCss();
      $this->autoprefixSaveSCSS($moduleSCSS, $css);

      if (strpos($scss, "generate_editor_styles")) {
        $cssEditor = $compiler->compileString('.editor-styles-wrapper .is-root-container {'.$scss.'}')->getCss();
        $this->autoprefixSaveSCSS(str_replace(".scss", ".editor.scss", $moduleSCSS), $cssEditor);
      }
    }
    catch (Exception $e) {
      echo "Compile error in $scss:<br>" . $e->getMessage();
    }
  }

  private function autoprefixSaveSCSS($moduleSCSS, $css) {
    $autoprefixer = new Autoprefixer('@charset "UTF-8";' . $css);
    $css = $autoprefixer->compile();
    return file_put_contents($this->sassToCssPath($moduleSCSS), $css);
  }

  public function enqueueEditorStyles() {
    $this->enqueueStyles($this->editorStyles);
  }

  public function enqueueThemeStyles() {
    $this->enqueueStyles($this->themeStyles);
  }

  public function enqueueStyles($stylesheets=[]) {
    foreach ($stylesheets as $file) {
      if (file_exists($file)) {        
        wp_enqueue_style("module-".basename($file, ".css"), $this->dirToPath($file), [], filemtime($file), 'all');
      }
    }
  }

  public function enqueueThemeScripts() {
    $this->enqueueScripts($this->themeScripts);
  }

  public function enqueueEditorScripts() {
    $this->enqueueScripts($this->editorScripts);
  }

  private function enqueueScripts($scripts=[]) {
    foreach ($scripts as $file) {
      if (file_exists($file)) {
        wp_enqueue_script("module-".basename($file, ".js"), $this->dirToPath($file), ["jquery"], filemtime($file), true);
      }
    }
  }

  private function dirToPath($directory) {
    return "/wp-content/" . explode("/wp-content/", $directory)[1];
  }

  private function sassToCssPath($scss) {
    return str_replace(".scss", ".css", $scss);
  }

  private function adminbar() {
    add_action('admin_bar_menu', function($wp_admin_bar) {
      if (current_user_can('administrator')) {
        $wp_admin_bar->add_node(
          [
            'id' => 'modularity-compile',
            'title' => 'Compile Modules',
            'href' => home_url("?compile"),
            'meta' => [
              'class' => 'modularity-compile',
              'title' => 'Compile Modules'
            ]
          ]
        );
      }
    }, 999);
  }

}

$Modularity = new Modularity();
