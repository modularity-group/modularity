
# Modularity

Modular Development-System for WordPress

Version 5, MIT License

https://www.modularity.group


## What this plugin does

1. Enqueues the theme `style.css` in frontend and admin block editor

2. Looking in `your-theme/modules/` and `../some-module/submodules/` to:

  - include `foomodule/foomodule.php`

  - enqueue `foomodule/foomodule.js`

  - enqueue `foomodule/foomodule.editor.js`

  - compile & autoprefix `foomodule/*.scss`

  - enqueue `foomodule/foomodule.css`

  - enqueue `foomodule/foomodule.editor.css`

  - auto-generate editor styles if string `generate_editor_styles` present in `*.scss`

3. Adds an admin information page with `compile modules` button
