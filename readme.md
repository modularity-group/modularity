
# Modularity

Modular Theme Development-System for WordPress

Version 5, MIT License

Copyright © 2023 [Modularity Group](https://modularity.group)

---

Modularity compiles and loads modular components from your theme. 

It regards all modules inside `your-theme/modules/` which may look like this:

![Example Theme and Module Screenshot](https://static.modularity.group/modularity-pro-docu-module-example.png)

The anatomy of a module is as shown below where all files are optional:

```
example/
  example.php --- included
  example.template.php
  example.scss --- compiled, prefixed, enqueued in front-end
  example.editor.scss --- compiled, prefixed, enqueued in editor
  example.block.scss --- compiled, prefixed
  example.js - enqueued in front-end
  example.editor.js --- enqueued in editor
  *.scss --- compiled, prefixed
  languages/ --- loaded
  submodules/ --- modules inside are processed alike
  readme.md
```

The `style.css` of your theme is enqueued in front-end and editor as well.

You can force to always compile everything via `define('MODULARITY_COMPILE', true)`.

---

For easier shared styles between front-end and editor you can have your editor styles be auto-generated.  
Add the key `// generate_editor_styles` into any `.scss` which creates the corresponding `.editor.css`.  
All code after this key is additionaly wrapped inside `.editor-styles-wrapper .is-root-container { }`.  
But you can't have an `.editor.scss` at the same time – if you need one, import your front-end styles there.  
