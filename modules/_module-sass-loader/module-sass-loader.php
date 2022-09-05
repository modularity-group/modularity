<?php defined("ABSPATH") or die;

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Padaliyajay\PHPAutoprefixer\Autoprefixer;

$themeModulesFolderUrl = is_dir(get_stylesheet_directory()."/modules") ? get_stylesheet_directory_uri()."/modules" : get_stylesheet_directory_uri();
$themeModulesFolderPath = is_dir(get_stylesheet_directory()."/modules") ? get_stylesheet_directory()."/modules" : get_stylesheet_directory();

if(!defined("DIST_URL")){
  define("DIST_URL", $themeModulesFolderUrl);
}
if(!defined("DIST_PATH")){
  define("DIST_PATH", $themeModulesFolderPath);
}

$cssFileModules = MODULES_DIR . "/modules.css";
$cssEditorFileModules = MODULES_DIR . "/modules.editor.css";
$cssFileTheme = DIST_PATH . "/bundle.css";
$cssEditorFileTheme = DIST_PATH . "/bundle.editor.css";

if( isset($_GET['c']) || isset($_GET['compile']) || !file_exists($cssFileModules) || !file_exists($cssEditorFileModules)|| !file_exists($cssFileTheme) || !file_exists($cssEditorFileTheme) ){
  require_once dirname( __FILE__ ).'/vendor/autoload.php';

  $compiler = new Compiler();
  $compiler->setOutputStyle( OutputStyle::COMPRESSED ); // this is not working. WHY?

  $cssContentModules = '';
  $cssEditorContentModules = '';
  $cssContentTheme = '';
  $cssEditorContentTheme = '';
  $compilerError = false;

  foreach (array("core", "config", "wp-block", "block", "feature") as $prefix) {
    foreach (glob(MODULES_DIR."/*") as $libraryModule) {
      $basename = basename($libraryModule);
      if (substr($basename, 0, strlen($prefix)) === $prefix) {
        $scssFile = MODULES_DIR."/$basename/$basename.scss";
        if(file_exists($scssFile)){
          $compiler->addImportPath( MODULES_DIR."/$basename/" );
          $scssContent = trim(@file_get_contents( $scssFile ));
          if($scssContent){
            try {
              $cssContentModules .= $compiler->compileString( $scssContent )->getCss();
              if(strpos($scssContent,'generate_editor_styles=true')){
                $cssEditorContentModules .= $compiler->compileString( '.editor-styles-wrapper .is-root-container {'.$scssContent.'}' )->getCss();
              }
            } catch(Exception $e1) {
              if( isset($_GET['c']) || isset($_GET['compile']) ) {
                echo 'Compiler error in '.$scssFile.':<br>' .$e1->getMessage();
              }
              $compilerError = true;
            }
          }
        }
        $scssEditorFile = MODULES_DIR."/$basename/$basename.editor.scss";
        if(file_exists($scssEditorFile)){
          $compiler->addImportPath( MODULES_DIR."/$basename/" );
          $scssEditorContent = trim(@file_get_contents( $scssEditorFile ));
          if($scssEditorContent){
            try {
              $cssEditorContentModules .= $compiler->compileString( $scssEditorContent )->getCss();
            } catch(Exception $e2) {
              if( isset($_GET['c']) || isset($_GET['compile']) ) {
                echo 'Compiler error in '.$scssEditorFile.':<br>' .$e2->getMessage();
              }
              $compilerError = true;
            }
          }
        }
      }
      // <SUBMODULES>
      if(glob(MODULES_DIR."/".$basename."/modules/*")){
        foreach (glob(MODULES_DIR."/".$basename."/modules/*") as $librarySubModule) {
          $basenameSub = basename($librarySubModule);
          if (substr($basenameSub, 0, strlen($prefix)) === $prefix) {
            $scssFile = MODULES_DIR."/$basename/modules/$basenameSub/$basenameSub.scss";
            if(file_exists($scssFile)){
              $compiler->addImportPath( MODULES_DIR."/$basename/modules/$basenameSub/" );
              $scssContent = trim(@file_get_contents( $scssFile ));
              if($scssContent){
                try {
                  $cssContentModules .= $compiler->compileString( $scssContent )->getCss();
                  if(strpos($scssContent,'generate_editor_styles=true')){
                    $cssEditorContentModules .= $compiler->compileString( '.editor-styles-wrapper .is-root-container {'.$scssContent.'}' )->getCss();
                  }
                } catch(Exception $e1) {
                  echo 'Compiler error in '.$scssFile.':<br>' .$e1->getMessage();
                  $compilerError = true;
                }
              }
            }
            $scssEditorFile = MODULES_DIR."/$basename/modules/$basenameSub/$basenameSub.editor.scss";
            if(file_exists($scssEditorFile)){
              $compiler->addImportPath( MODULES_DIR."/$basename/modules/$basenameSub/" );
              $scssEditorContent = trim(@file_get_contents( $scssEditorFile ));
              if($scssEditorContent){
                try {
                  $cssEditorContentModules .= $compiler->compileString( $scssEditorContent )->getCss();
                } catch(Exception $e2) {
                  echo 'Compiler error in '.$scssEditorFile.':<br>' .$e2->getMessage();
                  $compilerError = true;
                }
              }
            }
          }
        }
      }
      // </SUBMODULES>
    }
    foreach (glob("{$themeModulesFolderPath}/*") as $themeModule) {
      $basename = basename($themeModule);
      if (substr($basename, 0, strlen($prefix)) === $prefix) {
        $scssFile = "{$themeModulesFolderPath}/$basename/$basename.scss";
        if(file_exists($scssFile)){
          $compiler->addImportPath( "{$themeModulesFolderPath}/$basename/" );
          $scssContent = trim(@file_get_contents( $scssFile ));
          if($scssContent){
            try {
              $cssContentTheme .= $compiler->compileString( $scssContent )->getCss();
              if(strpos($scssContent,'generate_editor_styles=true')){
                $cssEditorContentTheme .= $compiler->compileString( '.editor-styles-wrapper .is-root-container {'.$scssContent.'}' )->getCss();
              }
            } catch(Exception $e1) {
              echo 'Compiler error in '.$scssFile.':<br>' .$e1->getMessage();
              $compilerError = true;
            }
          }
        }
        $scssEditorFile = "{$themeModulesFolderPath}/$basename/$basename.editor.scss";
        if(file_exists($scssEditorFile)){
          $compiler->addImportPath( "{$themeModulesFolderPath}/$basename/" );
          $scssEditorContent = trim(@file_get_contents( $scssEditorFile ));
          if($scssEditorContent){
            try {
              $cssEditorContentTheme .= $compiler->compileString( $scssEditorContent )->getCss();
            } catch(Exception $e2) {
              echo 'Compiler error in '.$scssEditorFile.':<br>' .$e2->getMessage();
              $compilerError = true;
            }
          }
        }
      }
      // <SUBMODULES>
      if(glob("{$themeModulesFolderPath}/$basename/modules/*")){
        foreach (glob("{$themeModulesFolderPath}/$basename/modules/*") as $themeSubModule) {
          $basenameSub = basename($themeSubModule);
          if (substr($basenameSub, 0, strlen($prefix)) === $prefix) {
            $scssFile = "{$themeModulesFolderPath}/$basename/modules/$basenameSub/$basenameSub.scss";
            if(file_exists($scssFile)){
              $compiler->addImportPath( "{$themeModulesFolderPath}/$basename/modules/$basenameSub/" );
              $scssContent = trim(@file_get_contents( $scssFile ));
              if($scssContent){
                try {
                  $cssContentTheme .= $compiler->compileString( $scssContent )->getCss();
                  if(strpos($scssContent,'generate_editor_styles=true')){
                    $cssEditorContentTheme .= $compiler->compileString( '.editor-styles-wrapper .is-root-container {'.$scssContent.'}' )->getCss();
                  }
                } catch(Exception $e1) {
                  echo 'Compiler error in '.$scssFile.':<br>' .$e1->getMessage();
                  $compilerError = true;
                }
              }
            }
            $scssEditorFile = "{$themeModulesFolderPath}/$basename/modules/$basenameSub/$basenameSub.editor.scss";
            if(file_exists($scssEditorFile)){
              $compiler->addImportPath( "{$themeModulesFolderPath}/$basename/modules/$basenameSub/" );
              $scssEditorContent = trim(@file_get_contents( $scssEditorFile ));
              if($scssEditorContent){
                try {
                  $cssEditorContentTheme .= $compiler->compileString( $scssEditorContent )->getCss();
                } catch(Exception $e2) {
                  echo 'Compiler error in '.$scssEditorFile.':<br>' .$e2->getMessage();
                  $compilerError = true;
                }
              }
            }
          }
        }
      }
      // </SUBMODULES>
    }
  }

  if($compilerError == false){
    $cssContentModules = str_replace( '@charset "UTF-8";','',$cssContentModules ); // fix this wih settings somehow
    $autoprefixer = new Autoprefixer( $cssContentModules );
    $prefixedCssContent = $autoprefixer->compile();
    file_put_contents( $cssFileModules, $prefixedCssContent );

    $cssEditorContentModules = str_replace( '@charset "UTF-8";','',$cssEditorContentModules ); // fix this wih settings somehow
    $autoprefixerEditor = new Autoprefixer( $cssEditorContentModules );
    $prefixedCssEditorContent = $autoprefixerEditor->compile();
    file_put_contents( $cssEditorFileModules, $prefixedCssEditorContent );

    $cssContentTheme = str_replace( '@charset "UTF-8";','',$cssContentTheme ); // fix this wih settings somehow
    $autoprefixer = new Autoprefixer( $cssContentTheme );
    $prefixedCssContent = $autoprefixer->compile();
    file_put_contents( $cssFileTheme, $prefixedCssContent );

    $cssEditorContentTheme = str_replace( '@charset "UTF-8";','',$cssEditorContentTheme ); // fix this wih settings somehow
    $autoprefixerEditor = new Autoprefixer( $cssEditorContentTheme );
    $prefixedCssEditorContent = $autoprefixerEditor->compile();
    file_put_contents( $cssEditorFileTheme, $prefixedCssEditorContent );
  } else {
    if( isset($_GET['c']) || isset($_GET['compile']) ) {
      echo '<br>Nothing compiled because of errors!';
    }
  }
}

add_action( 'wp_enqueue_scripts', function(){
  wp_enqueue_style(
    'core-module-style-loader-theme-style',
    get_stylesheet_directory_uri() . '/style.css',
    array('core-module-style-loader-theme'),
    filemtime( get_stylesheet_directory() . '/style.css' ),
    'all'
  );
  wp_enqueue_style(
    'core-module-style-loader-modules',
    MODULES_PATH . "/modules.css",
    array(),
    filemtime( MODULES_DIR . "/modules.css" ),
    'all'
  );
  wp_enqueue_style(
    'core-module-style-loader-theme',
    DIST_URL . "/bundle.css",
    array('core-module-style-loader-modules'),
    filemtime( DIST_PATH . "/bundle.css" ),
    'all'
  );
}, 20 );

add_action( 'enqueue_block_editor_assets', function(){
  wp_enqueue_style(
    'theme-style',
    get_stylesheet_directory_uri() . '/style.css',
    array('theme-editor-styles'),
    filemtime( get_stylesheet_directory() . '/style.css' ),
    'all'
  );
  wp_enqueue_style(
    'modules-editor-styles',
    MODULES_PATH . "/modules.editor.css",
    array(),
    filemtime( MODULES_DIR . "/modules.editor.css" ),
    'all'
  );
  wp_enqueue_style(
    'theme-editor-styles',
    DIST_URL . "/bundle.editor.css",
    array('modules-editor-styles'),
    filemtime( DIST_PATH . "/bundle.editor.css" ),
    'all'
  );
}, 20 );


add_action( 'admin_enqueue_scripts', function(){
  wp_enqueue_style(
    'core-module-style-loader-theme-style',
    get_stylesheet_directory_uri() . '/style.css',
    array(),
    filemtime( get_stylesheet_directory() . '/style.css' ),
    'all'
  );
}, 20 );

add_action('admin_bar_menu', function( $wp_admin_bar ) {
  if(current_user_can('administrator') || current_user_can('developer')){
    if(is_admin()){
      $baseurl = get_bloginfo( 'url' );
    } else {
      $baseurl = $_SERVER['REQUEST_URI'];
    }
    $compileUrl = add_query_arg( 'c', '', $baseurl );
    $args = array(
        'id' => 'style-loader',
        'title' => 'Compile Theme',
        'href' => $compileUrl,
        'meta' => array(
          'class' => 'style-loader',
          'title' => 'Compile Theme'
        )
    );
    $wp_admin_bar->add_node($args);
  }
}, 999);
