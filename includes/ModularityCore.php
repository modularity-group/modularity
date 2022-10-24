<?php defined("ABSPATH") or die;

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Padaliyajay\PHPAutoprefixer\Autoprefixer;

if (!class_exists("ModularityCore")) {

  class ModularityCore {

    private $siteStyles;
    private $siteScripts;
    private $editorStyles;
    private $editorScripts;

    public function __construct() {
      $this->enqueue();
    }

    public function init() {
      $this->siteStyles = [];
      $this->siteScripts = [];
      $this->editorStyles = [];
      $this->editorScripts = [];
      $this->adminpage();
    }

    public function enqueueSiteStyles() {
      $this->enqueueStyles($this->siteStyles);
    }

    public function enqueueSiteScripts() {
      $this->enqueueScripts($this->siteScripts);
    }

    public function enqueueEditorStyles() {
      $this->enqueueStyles($this->editorStyles);
    }

    public function enqueueEditorScripts() {
      $this->enqueueScripts($this->editorScripts);
    }

    private function enqueue() {
      add_action('wp_enqueue_scripts', [$this, 'enqueueSiteStyles'], 20);
      add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorStyles'], 20);
      add_action('wp_enqueue_scripts', [$this, 'enqueueSiteScripts'], 20);
      add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorScripts'], 20);
    }

    private function shouldCompile($sassFile) {
      return isset($_GET["c"]) || isset($_GET["compile"]) || !file_exists($this->sassToCssPath($sassFile));
    }

    private function compileSCSS($moduleSCSS) {
      if (!file_exists($moduleSCSS)) return;
      if (!class_exists("Compiler")) {
        require_once dirname(dirname(__FILE__)) . "/vendor/autoload.php";
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
        $this->saveAutoprefixSCSS($moduleSCSS, $css);
        if (strpos($scss, "generate_editor_styles")) {
          $cssEditor = $compiler->compileString('.editor-styles-wrapper .is-root-container {'.$scss.'}')->getCss();
          $this->saveAutoprefixSCSS(str_replace(".scss", ".editor.scss", $moduleSCSS), $cssEditor);
        }
      }
      catch (Exception $e) {
        echo "Compile error in $scss:<br>" . $e->getMessage();
      }
    }

    private function saveAutoprefixSCSS($moduleSCSS, $css) {
      $autoprefixer = new Autoprefixer('@charset "UTF-8";' . $css);
      $css = $autoprefixer->compile();
      return file_put_contents($this->sassToCssPath($moduleSCSS), $css);
    }

    private function enqueueStyles($stylesheets=[]) {
      foreach ($stylesheets as $file) {
        if (file_exists($file)) {
          wp_enqueue_style(
            $this->prefixEnqueue($file) . basename($file, ".css"), $this->dirToPath($file), [], filemtime($file), 'all'
          );
        }
      }
    }

    private function prefixEnqueue($file) {
      if (basename($file) === "style.css") {
        return "theme-";
      }
      if (strpos($file, "/plugins/")) {
        if (strpos($file, "/submodules/")) {
          return basename(dirname(dirname(dirname($file)))) . "-submodule-";
        }
        return "";
      }
      if (strpos($file, "/submodules/")) {
        return "module-" . basename(dirname(dirname(dirname($file)))) . "-submodule-";
      }
      return "module-";
    }

    private function enqueueScripts($scripts=[]) {
      foreach ($scripts as $file) {
        if (file_exists($file)) {
          wp_enqueue_script(
            $this->prefixEnqueue($file).basename($file, ".js"), $this->dirToPath($file), [], filemtime($file), true
          );
        }
      }
    }

    private function adminpage() {
      add_action('admin_menu', function() {
        add_menu_page("Modularity", "Modularity", "manage_options", "modularity", function() {
          ?>
            <div class="wrap">
              <?php do_action("modularity/admin_page_content") ?>
            </div>
          <?php
        }, $this->logo(), 59);
      });
    }

    protected function addStylesheet() {
      $style = get_stylesheet_directory() . "/style.css";
      $this->siteStyles[] = $style;
      $this->editorStyles[] = $style;
    }

    protected function dirToPath($directory) {
      return "/wp-content/" . explode("/wp-content/", $directory)[1];
    }

    protected function sassToCssPath($scss) {
      return str_replace(".scss", ".css", $scss);
    }

    protected function loadPHP($module) {
      add_action('plugins_loaded', function() use ($module) {
        $php = "$module/".basename($module).".php";
        if (!file_exists($php)) return;
        try {
          require_once($php);
        }
        catch (Exception $error) {
          add_action("admin_notices", function() {
            echo "<div class='notice notice-error'><p>Error in <b>$php</b>.</p></div>";
          });
        }
      });
    }

    protected function addAssets($module) {
      $name = basename($module);
      $this->siteStyles[] = "$module/$name.css";
      $this->editorStyles[] = "$module/$name.editor.css";
      $this->siteScripts[] = "$module/$name.js";
      $this->editorScripts[] = "$module/$name.editor.js";
    }

    protected function compileSassFiles($module) {
      foreach (glob("$module/[!_]*.scss") as $sassFile) {
        if ($this->shouldCompile($sassFile)) {
          $this->compileSCSS($sassFile);
        }
      }
    }

    protected function logo() {
      return 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4gPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNDkuMjE1IiBoZWlnaHQ9IjgxLjMxMSIgdmlld0JveD0iMCAwIDE0OS4yMTUgODEuMzExIj48ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMjU2LjY4IDE0Ni4xOTgpIHJvdGF0ZSgtNDUpIj48cGF0aCBkPSJNOSw5VjMyLjIzNkgzMi4yMzZWOUg5TTAsMEg0MS4yMzZWNDEuMjM2SDBaIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgzMTIuNDk5IDE3OS4wNTIpIiBmaWxsPSIjZmZmIj48L3BhdGg+PHBhdGggZD0iTTksOVYzMi4yMzZIMzIuMjM2VjlIOU0wLDBINDEuMjM2VjQxLjIzNkgwWiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMjQ4IDExNSkiIGZpbGw9IiNmZmYiPjwvcGF0aD48cGF0aCBkPSJNOSw5VjMyLjIzNkgzMi4yMzZWOUg5TTAsMEg0MS4yMzZWNDEuMjM2SDBaIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgyODAuNTIgMTE1KSIgZmlsbD0iI2ZmZiI+PC9wYXRoPjxwYXRoIGQ9Ik05LDlWMzIuMjM2SDMyLjIzNlY5SDlNMCwwSDQxLjIzNlY0MS4yMzZIMFoiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDI3OS45OCAxNDYuOTgpIiBmaWxsPSIjZmZmIj48L3BhdGg+PHBhdGggZD0iTTksOVYzMi4yMzZIMzIuMjM2VjlIOU0wLDBINDEuMjM2VjQxLjIzNkgwWiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMzEyLjQ5OSAxNDYuOTgpIiBmaWxsPSIjZmZmIj48L3BhdGg+PC9nPjwvc3ZnPiA=';
    }
  }

  $ModularityCore = new ModularityCore();
  $ModularityCore->init();
}
