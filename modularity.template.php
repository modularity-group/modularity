<?php defined("ABSPATH") or die; ?>

<link rel="stylesheet" href="<?= plugins_url() . '/modularity/modularity.css' ?>">

<div class="modularity wrap">

  <style>h2{margin-top:2rem}</style>

	<h1>Modularity</h1>

  <p>this system provides a real modular way of developing WordPress sites</p>
  <p>created by <a href="https://modularity.group">https://modularity.group</a></p>
  <p>version <?= get_plugin_data(dirname(__FILE__) . '/modularity.php')['Version'] ?>

  <h2>you have <?= count(Modularity::get_plugin_modules()) ?> module<?= count(Modularity::get_plugin_modules()) !==1 ? "s" : ""; ?> installed from your <a href="theme-editor.php?file=modules.json">modules.json</a></h2>

  <?php $installedAvailable = []; ?>
  <?php foreach (Modularity::get_plugin_modules() as $module): ?>
    <?php array_push($installedAvailable, basename($module)); ?>
    <code>
      <?php if (in_array(basename($module), Modularity::get_available_modules())): ?>
        <a href="https://github.com/modularity-group/<?= basename($module) ?>" target="_blank">
          <?= basename($module) ?>
          <small><?= Modularity::get_module_version($module) ?></small>
        </a>
        <div class="tooltip" style="display:none;"><div><?= Modularity::get_module_readme($module) ?></div></div>
      <?php else: ?>
        <a>
          <?= basename($module) ?>
          <small><?= Modularity::get_module_version($module) ?></small>
        </a>
        <div class="tooltip" style="display:none;"><div><?= Modularity::get_module_readme($module) ?></div></div>
      <?php endif; ?>
    </code>
  <?php endforeach; ?>

  <?php foreach (Modularity::get_available_modules() as $module): ?>
    <?php if (!in_array($module, $installedAvailable)): ?>
      <code class="is-lowlighted">
        <a target="_blank" href="https://github.com/modularity-group/<?= basename($module) ?>"><?= basename($module) ?></a>
      </code>
    <?php endif; ?>
  <?php endforeach; ?>

  <?php if (current_user_can("administrator")): ?>
    <form action="" method="post">
      <?php if (!isset($_GET["modules_deleted"])): ?>
        <input type="submit" value="↻" name="modularity_modules_delete" onclick="return confirm('Really delete and re-install your modules?');">
      <?php else: ?>
        <input type="submit" name="modularity_reload" value="✓">
      <?php endif; ?>
    </form>
  <?php endif; ?>

  <h2>you have <?= count(Modularity::get_theme_modules()) ?> custom module<?= count(Modularity::get_theme_modules()) !==1 ? "s" : ""; ?> in your <a href="themes.php">theme</a></h2>

  <?php foreach (Modularity::get_theme_modules() as $module): ?>
    <code>
      <a>
        <?= basename($module) ?>
        <small><?= Modularity::get_module_version($module) ?></small>
      </a>
      <div class="tooltip" style="display:none;"><div><?= Modularity::get_module_readme($module) ?></div></div>
    </code>
  <?php endforeach; ?>

  <br><br>

</div>
