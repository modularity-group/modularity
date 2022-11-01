<?php defined("ABSPATH") or die;

if (!class_exists("ModularityBase")) {

  class ModularityBase extends ModularityCore {

    public function __construct() {
      parent::__construct();
      self::load();
      self::adminpage();
    }

    private function load() {
      foreach (self::modules() as $module) {
        $this->loadPHP($module);
        $this->compileSassFiles($module);
        $this->addAssets($module);
      }
      $this->addStylesheet();
    }

    private function modules() {
      return array_merge(
        glob(WP_CONTENT_DIR . "/modules/[!_]*"),
        glob(WP_CONTENT_DIR . "/modules/[!_]*/submodules/[!_]*"),
        glob(get_stylesheet_directory() . "/modules/[!_]*"),
        glob(get_stylesheet_directory() . "/modules/[!_]*/submodules/[!_]*")
      );
    }

    private function adminpage() {
      add_action("modularity/admin_page_content", function(){
        ?>
          <h1><?= Modularity::name() ?></h1>
          <p>Modular Development-System for WordPress</p>
          <p>Version <?= Modularity::version() ?></p>
          <p>
            <a href="https://modularity.group" class="button button-primary" target="_blank">Get started</a>&nbsp;
            <a href="<?= home_url("?compile") ?>" class="button" target="_blank">Compile modules</a><br><br>
          </p>
        <?php
      }, 5);
    }
  }

  $ModularityBase = new ModularityBase();
}
