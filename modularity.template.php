<?php defined("ABSPATH") or die; ?>

<link rel="stylesheet" href="<?= plugins_url() . '/modularity/modularity.css?v=4.0.8' ?>">

<div class="modularity wrap">

  <style>h2{margin-top:2rem}</style>

	<h1>Modularity</h1>

  <p>this system provides a real modular way of developing WordPress sites</p>
  <p>created by <a href="https://modularity.group">https://modularity.group</a></p>
  <p>version <?= get_plugin_data(dirname(__FILE__) . '/modularity.php')['Version'] ?></p>

  <?php if (current_user_can("administrator")): ?>
    <a class="submit" href="theme-editor.php?file=modules.json">⚙ edit modules.json</a>
    <form action="" method="post">
      <?php if (!isset($_GET["modules_deleted"])): ?>
        <input type="submit" title="delete and re-install all modules" value="↻ force re-install modules" name="modularity_modules_delete" onclick="return confirm('Really delete and re-install your modules?');">
      <?php else: ?>
        <input type="submit" name="modularity_reload" value="✓ re-installed modules">
      <?php endif; ?>
    </form>
  <?php endif; ?>

  <!-- FREE MODULES -->

  <h2>free modules (<?= count(Modularity::get_plugin_modules()) ?>)</h2>

  <?php $installedAvailable = []; ?>
  <?php foreach (Modularity::get_plugin_modules() as $module): ?>
    <?php if (!in_array(basename($module), $AVAILABLE_PRO_MODULES)): ?>
      <?php array_push($installedAvailable, basename($module)); ?>
      <code>
        <?php if (in_array(basename($module), Modularity::get_available_free_modules())): ?>
          <a class="module module-free" href="https://github.com/modularity-group/<?= basename($module) ?>" target="_blank">
            <?= basename($module) ?>
          </a>
          <div class="tooltip" style="display:none;"><div><?= Modularity::get_module_readme($module) ?></div></div>
        <?php else: ?>
          <a class="module module-unknown">
            <?= basename($module) ?>
          </a>
          <div class="tooltip" style="display:none;"><div><?= Modularity::get_module_readme($module) ?></div></div>
        <?php endif; ?>
        <a href="#update" data-module="<?= basename($module) ?>" data-module-version="<?= Modularity::get_module_defined_version($module) ?>">
          <small><?= Modularity::get_module_version($module) ?></small>
        </a>
      </code>
    <?php endif; ?>
  <?php endforeach; ?>

  <?php foreach (Modularity::get_available_free_modules() as $module): ?>
    <?php if (!in_array($module, $installedAvailable)): ?>
      <code class="is-lowlighted">
        <a class="module module-free" target="_blank" href="https://github.com/modularity-group/<?= basename($module) ?>"><?= basename($module) ?></a>
      </code>
    <?php endif; ?>
  <?php endforeach; ?>

  <!-- PRO MODULES -->

  <?php if (count($INSTALLED_PRO_MODULES)): ?>
    <h2>
      <?php if (Modularity::is_license_valid()): ?>
        <span class="is-active dashicons dashicons-superhero" title="your license is active. pro modules can be installed."></span>
      <?php else: ?>
        <span class="is-inactive dashicons dashicons-superhero" title="license missing! pro modules can not be installed."></span>
      <?php endif; ?>
      pro modules (<?= count($INSTALLED_PRO_MODULES) ?>)
    </h2>

    <?php $installedAvailablePro = []; ?>
    <?php foreach ($INSTALLED_PRO_MODULES as $module): ?>
      <?php if (in_array(basename($module), $AVAILABLE_PRO_MODULES)): ?>
        <?php array_push($installedAvailablePro, basename($module)); ?>
        <code>
          <a class="module module-pro" href="https://github.com/modularity-group/<?= basename($module) ?>" target="_blank">
            <?= basename($module) ?>
          </a>
          <div class="tooltip" style="display:none;"><div><?= Modularity::get_module_readme($module) ?></div></div>
          <a href="#update" data-module="<?= basename($module) ?>" data-module-version="<?= Modularity::get_module_defined_version($module) ?>">
            <small><?= Modularity::get_module_version($module) ?></small>
          </a>
        </code>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php foreach ($AVAILABLE_PRO_MODULES as $module): ?>
      <?php if (!in_array($module, $installedAvailablePro)): ?>
        <code class="is-lowlighted">
          <a class="module module-pro" target="_blank" href="https://github.com/modularity-group/<?= basename($module) ?>"><?= basename($module) ?></a>
        </code>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- THEME MODULES -->

  <h2>theme modules (<?= count(Modularity::get_theme_modules()) ?>)</h2>

  <?php foreach (Modularity::get_theme_modules() as $module): ?>
    <code>
      <a class="module">
        <?= basename($module) ?>
      </a>
      <div class="tooltip" style="display:none;"><div><?= Modularity::get_module_readme($module) ?></div></div>
      <small><?= Modularity::get_module_version($module) ?></small>
    </code>
  <?php endforeach; ?>

  <br><br>

</div>

<script src="<?= plugins_url() . '/modularity/modularity.js?v=4.0.8' ?>"></script>
