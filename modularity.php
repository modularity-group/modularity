<?php defined("ABSPATH") or die;
/*
Plugin Name: Modularity
Plugin URI:  https://github.com/modularity5-group/modularity
Description: WordPress plugin for modular theme development
Version:     5.0.1
Author:      Modularity Group
Author URI:  https://www.modularity.group
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
    $this->enqueuer();
    $this->adminpage();
  }

  private function loader() {
    foreach ($this->modules() as $module) {
      $this->load($module);
    }
    $this->themeStyles[] = get_stylesheet_directory() . "/style.css";
    $this->editorStyles[] = get_stylesheet_directory() . "/style.css";
  }

  private function enqueuer() {
    add_action('wp_enqueue_scripts', [$this, 'enqueueThemeStyles'], 20);
    add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorStyles'], 20);
    add_action('wp_enqueue_scripts', [$this, 'enqueueThemeScripts'], 20);
    add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorScripts'], 20);
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
    $this->compileAllSCSS($module);
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

  private function compileAllSCSS($module) {
    foreach (glob("$module/[!_]*.scss") as $sassFile) {
      if ($this->shouldCompile($sassFile)) {
        $this->compileSCSS($sassFile);
      }
    }
  }

  private function shouldCompile($sassFile) {
    return isset($_GET["c"]) || isset($_GET["compile"]) || !file_exists($this->sassToCssPath($sassFile));
  }

  private function compileSCSS($moduleSCSS) {
    if (!file_exists($moduleSCSS)) return;
    if (!class_exists("Compiler")) {
      require_once dirname(__FILE__) . "/vendor/autoload.php";
    }
    $compiler = new Compiler();
    $compiler->addImportPath(dirname($moduleSCSS));
    $scss = trim(@file_get_contents($moduleSCSS));
    if (!$scss && file_exists($this->sassToCssPath($moduleSCSS))) {
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

  private function enqueueStyles($stylesheets=[]) {
    foreach ($stylesheets as $file) {
      if (file_exists($file)) {
        wp_enqueue_style(
          $this->enqueuePrefix($file) . basename($file, ".css"), $this->dirToPath($file), [], filemtime($file), 'all'
        );
      }
    }
  }

  private function enqueuePrefix($file) {
    if (strpos($file, "/submodules/")) {
      return "module-" . basename(dirname(dirname(dirname($file)))) . "-submodule-";
    }
    return "module-";
  }

  private function enqueueScripts($scripts=[]) {
    foreach ($scripts as $file) {
      if (file_exists($file)) {
        wp_enqueue_script(
          $this->enqueuePrefix($file).basename($file, ".js"), $this->dirToPath($file), [], filemtime($file), true
        );
      }
    }
  }

  private function dirToPath($directory) {
    return "/wp-content/" . explode("/wp-content/", $directory)[1];
  }

  private function sassToCssPath($scss) {
    return str_replace(".scss", ".css", $scss);
  }

  private function adminpage() {
    add_action('admin_menu', function() {
      add_menu_page("Modularity", "Modularity", "manage_options", "modularity", function() {
        echo '<div class="wrap">
          <h1>Modularity</h1>
          <p>
            Modular Development-System for WordPress.<br><br>
            <a href="https://www.modularity.group" class="button button-primary" target="_blank">Learn more</a>&nbsp;
            <a href="' . home_url("?compile") . '" class="button" target="_blank">Compile modules</a><br><br>
          </p>
        </div>';
        do_action('modularity/adminpage');
      }, $this->logo(), 59);
    });
  }

  private function logo() {
    return 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4gPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNDkuMjE1IiBoZWlnaHQ9IjgxLjMxMSIgdmlld0JveD0iMCAwIDE0OS4yMTUgODEuMzExIj48ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMjU2LjY4IDE0Ni4xOTgpIHJvdGF0ZSgtNDUpIj48cGF0aCBkPSJNOSw5VjMyLjIzNkgzMi4yMzZWOUg5TTAsMEg0MS4yMzZWNDEuMjM2SDBaIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgzMTIuNDk5IDE3OS4wNTIpIiBmaWxsPSIjZmZmIj48L3BhdGg+PHBhdGggZD0iTTksOVYzMi4yMzZIMzIuMjM2VjlIOU0wLDBINDEuMjM2VjQxLjIzNkgwWiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMjQ4IDExNSkiIGZpbGw9IiNmZmYiPjwvcGF0aD48cGF0aCBkPSJNOSw5VjMyLjIzNkgzMi4yMzZWOUg5TTAsMEg0MS4yMzZWNDEuMjM2SDBaIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgyODAuNTIgMTE1KSIgZmlsbD0iI2ZmZiI+PC9wYXRoPjxwYXRoIGQ9Ik05LDlWMzIuMjM2SDMyLjIzNlY5SDlNMCwwSDQxLjIzNlY0MS4yMzZIMFoiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDI3OS45OCAxNDYuOTgpIiBmaWxsPSIjZmZmIj48L3BhdGg+PHBhdGggZD0iTTksOVYzMi4yMzZIMzIuMjM2VjlIOU0wLDBINDEuMjM2VjQxLjIzNkgwWiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMzEyLjQ5OSAxNDYuOTgpIiBmaWxsPSIjZmZmIj48L3BhdGg+PC9nPjwvc3ZnPiA=';
  }

  public function enqueueThemeStyles() {
    $this->enqueueStyles($this->themeStyles);
  }

  public function enqueueThemeScripts() {
    $this->enqueueScripts($this->themeScripts);
  }

  public function enqueueEditorStyles() {
    $this->enqueueStyles($this->editorStyles);
  }

  public function enqueueEditorScripts() {
    $this->enqueueScripts($this->editorScripts);
  }
}

$Modularity = new Modularity();
