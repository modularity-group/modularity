# core-module-style-loader 

This core-module builds on WordPress and Modularity.

Compiles and bundles all module SCSS files.

---

Version: 1.5.0.x

Author: Matze @ https://modularity.group

License: MIT

---

Compile all

- `modules/{module-name}/{module-name}.scss`
- `modules/{module-name}/{module-name}.editor.scss`

and

- `theme/{module-name}/{module-name}.scss`
- `theme/{module-name}/{module-name}.editor.scss`

or

- `theme/modules/{module-name}/{module-name}.scss`
- `theme/modules/{module-name}/{module-name}.editor.scss`

and for each module

- `{module-name}/modules/{submodule-name}/{submodule-name}.scss`
- `{module-name}/modules/{submodule-name}/{submodule-name}.editor.scss`

files in order 

- core-*
- config-*
- wp-block-*
- feature-* 

to `/wp-content/modules/` and `/wp-content/themes/{theme}/` when url-parameter *?c* is set and enqueue to frontend and editor before themes style.css.

if `// generate_editor_styles=true` is found during compile in a module's scss file, this scss content will be wrapped with `.editor-style-wrapper` and saved also to `modules.editor.css` or `bundle.editor.css`.

Note: all modules of one type (f.e. config-*) are loaded in one run for both folders

---

1.5.0
- update autorefixer 1.3 => 1.4
- update scssphp 1.10.0 => 1.10.
- implement scssphp newer compile-method
- improve check of non existent basename assets to remove php warnings
- enqueue themes style.css also in admin

1.4.4
- better scope for editor styles (`.editor-styles-wrapper .is-root-container` instead of only `.editor-styles-wrapper`)

1.4.3
- better show/hide compile errors

1.4.2
- allow module prefix "block-"

1.4.1
- fix submodule load php-notices if none present

1.4.0
- ADD optional submodules feature: put (sub)modules in `/{modulename}/modules/` folder

1.3.0
- EXTEND loader check for theme-modules in `/themefolder/modules`. fallback `/themefolder` and save+enqueue bundle respectively
- UPDATE scsspp 1.9.0 > 1.10.0

1.2.3
- Add option to force editor-style creation of standard scss files with flag: `// generate_editor_styles=true` saved in respective file

1.2.2
- UPDATE sabberworm/php-css-parser (8.4.0)
- UPDATE scssphp/scssphp (v1.9.0)

1.2.1
- Rename theme-assets from `theme` to `bundle`

1.2.0
- update new asset structure: `/modules` and `/theme/*`  

1.1.1
- update modules and dist path

1.1.0 (Matze)
- Update scssphp 1.6.0 > 1.8.1

1.0.3
- also load core modules scss files

1.0.2
- change order of style.css and compiled styles to overwrite css variables

1.0.1
- only allow admins and developers to see the button

0.5.0
- load also *library* modules
- new core module structure
- updated scssphp from 1.5.2 > 1.6.0
