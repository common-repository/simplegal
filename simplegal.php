<?php
/*
Plugin Name: SimpleGal
Plugin URI: http://www.dath.info/webwork/wp-plugins/simplegal/
Description: Image-Gallery in 5 simple Steps (with Lightbox-Support!)
Version: 1.2
Author: Daniel Theiss (dath)
Author URI: http://www.dath.info/

*/

/****
    SimpleGal - Gallery Plugin for WordPress
    Copyright (c) 2010-2012  Daniel Theiss

    This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
    You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
****/


/** Internal Functions **/
function simplegal_readinfo($imgdir,$infofile,$images=array()) {
  
    $file = $imgdir."/".$infofile;
    $titles = array();
    $i=0;
   
    $fp = @fopen($file, "r");
    // Skips UTF-8 bom, if there
    $bom = fread($fp, 3);
    if($bom != b"\xEF\xBB\xBF") 
      rewind($fp);
    
    while($line=fgets($fp,1024)) {
     $titles[$i] = trim($line);
     $i++;
    }
    fclose($fp);

    if(!empty($images)) {
      $n = count($titles);
      for($j=0;$j<$n;$j++) {
        $str_split = explode('::', $titles[$j], 2);
        $str_split[0] = trim($str_split[0]);
        if(in_array($str_split[0], $images)) {
          $arr['images'][] = $str_split[0];
          $arr['titles'][] = trim($str_split[1]);
        }
      }
      return $arr;
    }  
    
    return $titles;   
}
  
function simplegal_readimages($imgdir,$ext,$order="ASC") {
    
    $ext = explode(',',$ext);
    $i=0;
    $images = array();
    
    $dir = openDir($imgdir);
    while($file = readDir($dir)) { 
     if($file != "." && $file != ".." &&
        in_array(strtolower(substr($file, -4)),$ext)) {
          $images[$i] = $file;
          $i++;
     }
    }
    closeDir($dir);
    
    switch ($order) {
    case "ASC":
      sort($images);
      break;
    case "DESC":
      rsort($images);
      break;
    default:
      sort($images);
      break;    
    }
    return $images;  
  
}

/** WP-Shortcode and Gallery-Output **/
function simplegal_out($atts) {
  
  extract(shortcode_atts(array(
		'gname' => 'gallery',
		'dir' => 'images',
		'ext' => '.jpg,.png,.gif,.bmp',
		'order' => 'ASC',
	), $atts));
  
  $gname = $atts['gname'];
  $ext = $atts['ext'];
  $order = $atts['order'];
  
  // Get options from database 
  $dir = get_option("simplegal_dir_path")."/".$atts['dir'];
  $infofile = get_option('simplegal_info_file');
  
  if(file_exists($dir)) {               // Check Image-Directory
    if(file_exists($dir."/thumbs")) {   // Check Thumbnail-Directory
    
      // Set default values
      if($gname == "" || $gname == " ")
        $gname = "gallery";
      if($ext == "" || $ext == " ")
        $ext = ".jpg,.png,.gif,.bmp";
      if($order == "" || $order == " ")
        $order = "ASC";
      
      if($order == "LIST") {
        if(!file_exists("$dir/$infofile"))
          return _e("Error! The Info-File doesn't exist!",'simplegal'); // Return Error-Message
        
        $list_arr = simplegal_readinfo($dir,$infofile,simplegal_readimages($dir,$ext));
        $images = $list_arr['images'];
        $titles = $list_arr['titles'];      
      } else {
        $images = simplegal_readimages($dir,$ext,$order); 
        if(file_exists("$dir/$infofile")) {
            $titles = simplegal_readinfo($dir,$infofile);
        }
      }  
      $pr_out = '<ul class="simplegal">'."\n";
        
        $n = count($images);
        if($n == 0)
          return _e("Error! No images found!",'simplegal'); // Return Error-Message
        for($i=0;$i<$n;$i++) {
                    
          $pr_out .= '<li><a href="'.get_option('home').'/'.$dir.'/'.$images[$i].'" title="'.$titles[$i].'" rel="lightbox['.$gname.']">';
          $pr_out .= '<img src="'.get_option('home').'/'.$dir.'/thumbs/'.$images[$i].'" alt="'.$titles[$i].'" />'."\n";
          $pr_out .= '</a></li>'."\n";
                      
        }
      
      $pr_out .= '</ul>'."\n"; 
      $pr_out .= '<br class="clear" />';
    
    	return "{$pr_out}";
  	}
  	else
  	 return _e("Error! The Thumbnail-Directory doesn't exist!",'simplegal'); // Return Error-Message
	}
	else
	  return _e("Error! The Image-Directory doesn't exist!",'simplegal'); // Return Error-Message

}

add_shortcode('simplegal', 'simplegal_out');

/** Stylesheet **/
function simplegal_make_style() {
  echo '<link rel="stylesheet" href="'. WP_PLUGIN_URL .'/simplegal/style.css" type="text/css" media="screen" />';
  if(get_option("template") == "default") {
    echo "\n";
    echo '<style type="text/css" media="screen">'."\n";
    echo '    /** ajdustment for kubrick-themes **/'."\n";
    echo '    .simplegal img { margin-left: 10px; }'."\n";
    echo '</style>'."\n";
  }
} 

add_action('wp_head','simplegal_make_style');

/** Option-Page **/
function simplegal_option_page() {
?>
    <style type="text/css" media="screen">
      .simplegal_admin div.codebox {
        font-family: Consolas,Monaco,monospace;
        background-color: #eaeaea;
        padding: .3em;
      }
    </style>
    <div class="wrap simplegal_admin">
      <h2>SimpleGal</h2>
        
        <h3 class="tilte"><?php _e('Information','simplegal'); ?></h3>
        
        <p><?php _e('With SimpleGal you can simply create image-galleries.','simplegal'); ?><br />
        <?php _e('Just upload your images, add the SimpleGal Shortcode to your posts or pages and the gallery will appear.','simplegal'); ?><br />
        <?php _e('For an optimal gallery view you should install a lightbox plugin.','simplegal'); ?>  
        </p>
        <br />
        
        <h3 class="tilte"><?php _e('How to use','simplegal'); ?></h3>
        
          <p><strong><?php _e('Present your images in just five simple steps:','simplegal'); ?></strong></p>
          
          <ol style="list-style: decimal outside; padding-left: 15px">
            <li><?php _e('Choose your images and put them into a folder.','simplegal'); ?></li>
            <li><?php _e('Create small thumbnails of your images and put them with the same name like the original images into a sub-folder named','simplegal'); ?> <code>thumbs</code>.</li>
            <li><?php _e('Create a text-file and list the titles of your images (each in one line) in correct order.','simplegal'); ?><br />
            <?php printf( __('Name this info file like you have chosen in configuration %s and put it in the folder to your images.','simplegal'), '(<strong>'.__('default:','simplegal').'</strong> <code>info.txt</code>)'); ?></li>
            <li><?php _e('Upload your data into the image folder you also have chosen in configuration','simplegal'); ?> (<strong><?php _e('default:','simplegal'); ?></strong> <code>wp-content/gallery</code>).</li>
            <li><?php printf( __('Add the Shortcode %s to your post or page.','simplegal'), '<code>[simplegal gname="" dir=""]</code>'); echo ' '; printf( __('Optionally you can add %1$s and %2$s as attributes. (For details see below.)','simplegal') , '<code>ext=""</code>', '<code>order=""</code>'); ?><br />
            <?php printf( __('Give your gallery a name %1$s and type in the name of your image folder %2$s.','simplegal'), '(<em>gname</em>)', '(<em>dir</em>)'); ?><br />
            <?php _e('Optionally you can define the filetypes of the gallery-images by listing the file extensions separated by commas','simplegal'); ?> (<em>ext</em>) (<strong><?php _e('default:','simplegal'); ?></strong> <code>.jpg,.png,.gif,.bmp</code>).<br />
            <?php _e('You can also define the image-ordering of your gallery','simplegal'); ?> (<em>order</em>) (<strong><?php _e('default:','simplegal'); ?></strong> <code>ASC</code>). <br />
            <strong><?php _e('Possible values for the order attribute:','simplegal');?></strong> <?php printf( __('%1$s for ascending order, %2$s for descending order or %3$s for individual order, defined in the info file.','simplegal'), '<code>ASC</code>', '<code>DESC</code>', '<code>LIST</code>'); ?></li>
          </ol>
          <p><?php _e("That's it!",'simplegal'); ?></p>
        <br />
        
        <h3 class="tilte"><?php _e('The Info File','simplegal'); ?></h3>
        <strong><?php _e('The info file is a simple text-file that includes the titles of your gallery-images.','simplegal'); ?></strong><br />
        <p><?php _e('If you only want to give you images a title then you have to list one per line. The order should be the same order your images are ordered (ascending/descending).','simplegal'); ?><br />
        <p><strong><?php _e('Here a short example:','simplegal'); ?></strong>
        <?php printf( __('You have three images (%1$s, %2$s, %3$s) that should be displayed in ascending order','simplegal'), '<em>sunset.jpg</em>', '<em>waterlily.jpg</em>', '<em>winter.jpg</em>'); ?> (<code>order="ASC"</code>).<br />
        <?php _e('To assign them with titles, create an info file like this:','simplegal'); ?></p>
        <div class="codebox" style="font-family: Consolas">
          <?php _e('Beautiful sunset','simplegal'); ?><br />
          <?php _e('Waterlily','simplegal'); ?><br />
          <?php _e('It was very cold in last winter.','simplegal'); ?>
        </div>
        <p><?php printf( __( 'If your images should be displayed in descending order %s, you also have to list your titles in reverse order for correct assignment.', 'simplegal' ), ' (<code>order="DESC"</code>)' ); ?></p>
        <strong><?php _e('The info file can also contain the individual order of your gallery-images.','simplegal'); ?></strong><br />
        <p><?php printf( __( 'If you want to display your images in an individual order %s, you have to add the filenames of your images in the info file.', 'simplegal' ), ' (<code>order="LIST"</code>)' ); ?><br />
        <?php _e('So each line in the info file has to be in the following format now:','simplegal'); ?> <code><?php printf( __('filename %s imagetitle','simplegal'), '::'); ?></code>.<br />
        <?php _e('For the short example above, the info file for individual order would look like this:','simplegal'); ?></p>
        <div class="codebox">
        winter.jpg :: <?php _e('It was very cold in last winter.','simplegal'); ?><br />
        sunset.jpg :: <?php _e('Beautiful sunset','simplegal'); ?><br />
        waterlily.jpg :: <?php _e('Waterlily','simplegal'); ?><br />
        </div>
        <br />
        
        <h3 class="title"><?php _e('Configuration','simplegal'); ?></h3>
        <form method="post" action="options.php">
          <?php wp_nonce_field('update-options'); ?>
          
          <table class="form-table">
          
          <tr valign="top">
          <th scope="row"><?php _e('Path to image folder:','simplegal'); ?></th>
          <td><input type="text" name="simplegal_dir_path" value="<?php echo get_option('simplegal_dir_path'); ?>" />
          <span class="setting-description"><?php _e('Input path to image folder which contains your images in sub-folders.','simplegal'); ?></span>
          </td>
          </tr>
          
          <tr valign="top">
          <th scope="row"><?php _e('Name of info file:','simplegal'); ?></th>
          <td><input type="text" name="simplegal_info_file" value="<?php echo get_option('simplegal_info_file'); ?>" />
          <span class="setting-description"><?php _e('Define the name of info file which contains the image titles.','simplegal'); ?></span>
          </td>
          </tr> 
          </table>
          
          <input type="hidden" name="action" value="update" />
          <input type="hidden" name="page_options" value="simplegal_dir_path,simplegal_info_file" />
          
          <p class="submit">
          <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
          </p>
        </form>
        
    </div>

<?php
}

/** Load Language-File **/
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'simplegal', null, $plugin_dir );

 
/** Add Options **/
function simplegal_add_menu() {
  add_option("simplegal_dir_path","wp-content/gallery");
  add_option("simplegal_info_file","info.txt");
	add_options_page('SimpleGal Plugin', 'SimpleGal', 9, __FILE__, 'simplegal_option_page');
}

add_action('admin_menu', 'simplegal_add_menu');

/** Check for hook **/
if ( function_exists('register_uninstall_hook') )
	register_uninstall_hook(__FILE__, 'simplegal_deinstall');
 
/** Delete options in database **/
function simplegal_deinstall() {
	delete_option('simplegal_dir_path');
	delete_option('simplegal_info_file');
}

?>
