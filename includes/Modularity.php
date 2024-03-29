<?php defined("ABSPATH") or die;

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Padaliyajay\PHPAutoprefixer\Autoprefixer;

if (!class_exists("Modularity")) {

  class Modularity {

    private $siteStyles = [];
    private $siteScripts = [];
    private $editorStyles = [];
    private $editorScripts = [];

    public function __construct() {}

    public function init() {
      if (defined('DOING_AJAX') && DOING_AJAX) {
        $this->load(false);
      }
      else {
        $this->load();
        $this->enqueue();
        $this->admin();
      }
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

    public function getAdminContent() {
      $this->adminStyle();
      $this->adminContent();
    }

    private function load($styles=true) {
      foreach ($this->modules() as $module) {
        $this->loadPHP($module);
        if (!$styles) continue;
        $this->deleteCompiledCssFiles($module);
        $this->compileSassFiles($module);
        $this->addAssets($module);
      }
      if ($styles) {
        $this->addStylesheet();
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

    private function shouldCompile($sassFile) {
      $cssFile = $this->sassToCssPath($sassFile);
      return !file_exists($cssFile) || filemtime($sassFile) !== filemtime($cssFile);
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
        $this->prefixAndSaveCSS($moduleSCSS, $css);
        $this->generateEditorCSS($compiler, $moduleSCSS, $scss);
      }
      catch (Exception $e) {
        add_action("wp_body_open", function() use ($e, $moduleSCSS) {
          echo "<code><b>" . basename($moduleSCSS) . "</b> " . $e->getMessage() . "</code>";
        }, 100);
      }
    }

    private function generateEditorCSS($compiler, $scssFile, $scssCode) {
      if (!strpos($scssCode, "generate_editor_styles")) {
        $cssFileEditor = str_replace(".scss", ".editor.css", $scssFile);
        if (file_exists($cssFileEditor)) {
          unlink($cssFileEditor);
          $this->compileSCSS(str_replace(".scss", ".editor.scss", $scssFile));
        }
        return;
      }
      try {
        $scssCodeEditor = str_replace("generate_editor_styles", "\n.editor-styles-wrapper {", $scssCode)."}";
        $cssCodeEditor = $compiler->compileString($scssCodeEditor)->getCss();
        $this->prefixAndSaveCSS(str_replace(".scss", ".editor.scss", $scssFile), $cssCodeEditor);
      }
      catch (Exception $e) {
        add_action("wp_body_open", function() use ($e, $scssFile) {
          echo "<code><b>" . basename($scssFile) . "</b> " . $e->getMessage() . "</code>";
        }, 100);
      }
    }

    private function prefixAndSaveCSS($moduleSCSS, $css) {
      $autoprefixer = new Autoprefixer('@charset "UTF-8";' . $css);
      $css = $autoprefixer->compile(false);
      if (file_exists($moduleSCSS)) touch($moduleSCSS);
      return file_put_contents($this->sassToCssPath($moduleSCSS), $css);
    }

    private function prefixEnqueue($file) {
      if (basename($file) === "style.css") return "theme-";
      $isSubmodule = strpos($file, "/submodules/");
      if (strpos($file, "/plugins/")) {
        return $isSubmodule ? basename(dirname(dirname(dirname($file)))) . "-submodule-" : "";
      }
      if ($isSubmodule) {
        return "theme-module-" . basename(dirname(dirname(dirname($file)))) . "-submodule-";
      }
      return "theme-module-";
    }

    private function enqueueStyles($stylesheets=[]) {
      foreach ($stylesheets as $file) {
        if (file_exists($file)) {
          wp_enqueue_style(
            $this->prefixEnqueue($file) . basename($file, ".css"),
            $this->dirToPath($file), [], filemtime($file), 'all'
          );
        }
      }
    }

    private function enqueueScripts($scripts=[]) {
      foreach ($scripts as $file) {
        if (file_exists($file)) {
          wp_enqueue_script(
            $this->prefixEnqueue($file) . basename($file, ".js"),
            $this->dirToPath($file), [], filemtime($file), true
          );
        }
      }
    }

    private function admin() {
      $this->adminpage();
      add_action("modularity/admin_page_content", [$this, 'getAdminContent'], 5);
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

    private function adminStyle() {
      ?>
        <style>
          .toplevel_page_modularity .button:not(.button-primary) {color:#896CD6;border-color:#896CD6}
          .toplevel_page_modularity .button:hover,
          .toplevel_page_modularity .button:active,
          .toplevel_page_modularity .button:focus {color:#896CD6;border-color:#896CD6;background:#fff}
          .toplevel_page_modularity .button-primary {background:#896CD6;border-color:#896CD6}
          .toplevel_page_modularity .button-primary:hover,
          .toplevel_page_modularity .button-primary:focus {background:#795CC6;border-color:#795CC6;color:#fff}
          .toplevel_page_modularity .button.button-secondary {color:#896CD6;border-color:#896CD6}
          .toplevel_page_modularity .button:focus {box-shadow: 0 0 0 1px #896CD6}
        </style>
      <?php
    }

    private function adminContent() {
      ?>
        <?php if (defined('MODULARITY_COMPILE')): ?>
          <div class="notice notice-info">
            <p><b>MODULARITY_COMPILE</b> is defined! Don't forget to disable forced compiling.</p>
          </div>
        <?php endif; ?>
        <h1><?= MODULARITY_NAME ?></h1>
        <p>Modular Development-System for WordPress</p>
        <p>Version <?= MODULARITY_VERSION ?></p>
        <a href="https://modularity.group" class="button button-primary" target="_blank">Get started</a>&nbsp;
        <?php if (MODULARITY_NAME === "Modularity"): ?>
          <a href="/<?= basename(WP_CONTENT_DIR) ?>/plugins/modularity/readme.md" class="button button-secondary" target="_blank">Readme</a>&nbsp;
        <?php endif; ?>
      <?php
    }

    protected function enqueue($priority=25) {
      add_action('wp_enqueue_scripts', [$this, 'enqueueSiteStyles'], $priority);
      add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorStyles'], $priority);
      add_action('wp_enqueue_scripts', [$this, 'enqueueSiteScripts'], $priority);
      add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorScripts'], $priority);
    }

    protected function addStylesheet() {
      $style = get_stylesheet_directory() . "/style.css";
      $this->siteStyles[] = $style;
      $this->editorStyles[] = $style;
    }

    protected function loadLanguages($module) {
      if (is_dir("$module/languages")) {
        add_action('init', function() use ($module) {
          load_plugin_textdomain(basename($module), false, basename($module)."/languages/");
        });
      }
    }

    protected function dirToPath($directory) {
      $contentFolder = defined("WP_CONTENT_FOLDERNAME") ? WP_CONTENT_FOLDERNAME : "wp-content";
      return "/$contentFolder/" . explode("/$contentFolder/", $directory)[1];
    }

    protected function sassToCssPath($scss) {
      return str_replace(".scss", ".css", $scss);
    }

    protected function cssToScssPath($css) {
      return str_replace(".css", ".scss", $css);
    }

    protected function loadPHP($module) {
      add_action('plugins_loaded', function() use ($module) {
        $php = "$module/".basename($module).".php";
        if (!file_exists($php)) return;
        try {
          require_once $php;
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
        if ($this->shouldCompile($sassFile) || defined("MODULARITY_COMPILE")) {
          $this->compileSCSS($sassFile);
        }
      }
    }

    protected function deleteCompiledCssFiles($module) {
      $moduleCss = $module . "/" . basename($module) . ".css";
      if (!file_exists($moduleCss)) return;
      if (file_exists($this->cssToScssPath($moduleCss))) return;
      return unlink($moduleCss) && rmdir(dirname($moduleCss));
    }

    protected function logo() {
      return 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4gPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNDkuMjE1IiBoZWlnaHQ9IjgxLjMxMSIgdmlld0JveD0iMCAwIDE0OS4yMTUgODEuMzExIj48ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMjU2LjY4IDE0Ni4xOTgpIHJvdGF0ZSgtNDUpIj48cGF0aCBkPSJNOSw5VjMyLjIzNkgzMi4yMzZWOUg5TTAsMEg0MS4yMzZWNDEuMjM2SDBaIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgzMTIuNDk5IDE3OS4wNTIpIiBmaWxsPSIjZmZmIj48L3BhdGg+PHBhdGggZD0iTTksOVYzMi4yMzZIMzIuMjM2VjlIOU0wLDBINDEuMjM2VjQxLjIzNkgwWiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMjQ4IDExNSkiIGZpbGw9IiNmZmYiPjwvcGF0aD48cGF0aCBkPSJNOSw5VjMyLjIzNkgzMi4yMzZWOUg5TTAsMEg0MS4yMzZWNDEuMjM2SDBaIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgyODAuNTIgMTE1KSIgZmlsbD0iI2ZmZiI+PC9wYXRoPjxwYXRoIGQ9Ik05LDlWMzIuMjM2SDMyLjIzNlY5SDlNMCwwSDQxLjIzNlY0MS4yMzZIMFoiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDI3OS45OCAxNDYuOTgpIiBmaWxsPSIjZmZmIj48L3BhdGg+PHBhdGggZD0iTTksOVYzMi4yMzZIMzIuMjM2VjlIOU0wLDBINDEuMjM2VjQxLjIzNkgwWiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMzEyLjQ5OSAxNDYuOTgpIiBmaWxsPSIjZmZmIj48L3BhdGg+PC9nPjwvc3ZnPiA=';
    }
  }

  $Modularity = new Modularity();
  $Modularity->init();
}
