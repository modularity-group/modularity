<?php defined("ABSPATH") or die; ?>

<style>
  .modularity {
    width: 800px;
    max-width: 100%;
  }
  .modularity code {
    display: inline-block;
    margin: 0 10px 10px 0;
    border-radius: 4px;
    background: white;
    padding: 3px 5px 2px 5px;
  }
  .modularity code a {
    color: inherit;
  }
  .modularity code.is-lowlighted {
    opacity: 0.41;
  }
  .modularity form {
    display: inline-block;
  }
  .modularity [type="submit"] {
    background: white;
    border: none;
    padding: 3px 7px 2px 7px;
    border-radius: 4px;
    color: #555;
    cursor: pointer;
  }
  .modularity [type="submit"]:hover {
    color: red;
  }
  .modularity [type="submit"][value="✓"] {
    pointer-events: none;
  }
</style>

<div class="modularity wrap">

  <style>h2{margin-top:2rem}</style>

	<h1>Modularity</h1>

  <p>this system provides a real modular way of developing WordPress sites</p>
  <p>created by <a href="https://modularity.group">https://modularity.group</a></p>
  <p>version <?= get_plugin_data(dirname(__FILE__) . '/modularity.php')['Version'] ?>

  <h2>you have <?= count(Modularity::get_plugin_modules()) ?> modules installed from your <a href="theme-editor.php?file=modules.json">modules.json</a></h2>

  <?php $installedAvailable = []; ?>
  <?php foreach (Modularity::get_plugin_modules() as $module): ?>
    <?php array_push($installedAvailable, basename($module)); ?>
    <code>
      <?php if (in_array(basename($module), Modularity::get_available_modules())): ?>
        <a target="_blank" href="https://github.com/modularity-group/<?= basename($module) ?>"><?= basename($module) ?></a>
      <?php else: ?>
        <?= basename($module) ?>
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
        <input type="submit" value="✓" onclick="return false;">
      <?php endif; ?>
    </form>
  <?php endif; ?>

  <h2>you have <?= count(Modularity::get_theme_modules()) ?> custom modules in your <a href="themes.php">theme</a></h2>

  <?php foreach (Modularity::get_theme_modules() as $module): ?>
    <code>
      <?= basename($module) ?>
    </code>
  <?php endforeach; ?>

  <br><br>

</div>
