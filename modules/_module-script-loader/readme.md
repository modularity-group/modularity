# core-module-script-loader

This core-module builds on WordPress and Modularity.

Bundles (concatenates) all module JS files.

---

Version: 1.3.1.x

Author: Ben @ https://modularity.group

License: MIT

---

Compile all

- `modules/{module-name}/{module-name}.js`
- `modules/{module-name}/{module-name}.editor.js`

and

- `theme/{module-name}/{module-name}.js`
- `theme/{module-name}/{module-name}.editor.js`

or

- `theme/modules/{module-name}/{module-name}.js`
- `theme/modules/{module-name}/{module-name}.editor.js`

and for each module

- `{module-name}/modules/{submodule-name}/{submodule-name}.js`
- `{module-name}/modules/{submodule-name}/{submodule-name}.editor.js`

files in order 

- core-*
- config-*
- wp-block-*
- feature-* 

to `/wp-content/modules/` and `/wp-content/themes/{theme}/` when url-parameter *?c* is set and enqueue to frontend and editor.

Note: all modules of one type (f.e. config-*) are loaded in one run for both folders

---

1.3.2 | allow module prefix "block-"

1.3.1 | FIX module loading-error

1.3.0 | ADD optional submodules feature: put (sub)modules in `/{modulename}/modules/` folder

1.2.0 | loader check for theme-modules in `/themefolder/modules`. fallback `/themefolder` and save+enqueue bundle respectively

1.1.1 | Rename theme-assets from `theme` to `bundle`

1.1.0 | update new asset structure: `/modules` and `/theme/*`  

1.0.1 | update modules and dist path

0.3.0 | updated new core module structure
