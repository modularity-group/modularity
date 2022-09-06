
# Modularity

Modular WordPress theme development system.

Version 5.0.0.b3

https://modularity.group


## What is does

1. Enqueues the theme `style.css` in frontend and admin block editor

2. Looking in `wp-content/modules/` and `your-theme/modules/`

  - includes `foomodule/foomodule.php`

  - enqueues `foomodule/foomodule.js`

  - enqueues `foomodule/foomodule.editor.js`

  - compiles & autoprefixes `foomodule/foomodule.scss` and enqueues resulting `foomodule/foomodule.css`

  - compiles & autoprefixes `foomodule/foomodule.editor.scss` and enqueues `foomodule/foomodule.editor.css`

  - auto-generates block editor styles if string `generate_editor_styles` is present in `foomodule.scss`

3. Looking in `foomodule/submodules/barmodule` to do the same

4. Adds a `Compile Modules` button to the adminbar for administrators

5. Allows external modules installation from theme `modules.json` via GitHub or Zip-URL