<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 HMVC Based Scalable Software.
 *
 * @package         obullo
 * @author          obullo.com
 * @filesource
 * @license
 */

/**
 * Obullo Html Helper
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Html
 * @author      Ersin Guvenc
 * @link
 */
// --------------------------------------------------------------------

/**
* Build css files in <head> tags
*
* css('welcome.css');
* css('subfolder/welcome.css')
* css('../module/welcome.css');  from /modules dir
* css(array('welcome.css', 'hello.css'));
* css('#main {display: block; color: red;}', 'embed');
*
* @author   Ersin Guvenc
* @param    mixed   $filename array or string
* @param    string  $title_or_embed
* @param    string  $media  'all' or 'print' etc..
* @version  0.1
* @version  0.2 added $path variable
* @version  0.2 added _ent->css_folder variable
* @version  0.3 depreciated $path param
* @return   string
*/
if( ! function_exists('css') )
{
    function css($href, $title_or_embed = '', $media = '', $rel = 'stylesheet', $index_page = FALSE)
    {
        $ob = this();
        
        if($title_or_embed == 'embed')
        {
            $css = '<style type="text/css" ';
            $css.= ($media != '') ? 'media="'.$media.'" ' : '';
            $css.= '>';
            $css.= $href;
            $css.= "</style>\n";
            
            return $css;
        }
            
        $title = $title_or_embed;
        $link = '<link ';

        $_ob = lib('ob/Storage');   // obullo changes ..

        // When user use view_set_folder('css', 'iphone'); ..  /public/iphone/css/welcome.css
        $extra_path = '';
        if( isset($_ob->view->css_folder{1}) )
        {
            $extra_path = $_ob->view->css_folder;
        }

        if (is_array($href))
        {
            $ext = 'css';
            if(strpos($href, 'js/') === 0)
            {
                $ext  = 'js';
                $href = substr($href, 3);
            }
            
            $link = '';
                                
            foreach ($href as $v)
            {
                $link .= '<link ';

                $v = ltrim($v, '/');   // remove first slash  ( Obullo changes )

                if ( strpos($v, '://') !== FALSE)
                {
                    $link .= ' href="'. $v .'" ';
                }
                else
                {
                    $link .= ' href="'. _get_public_path($v, $extra_path, $ext) .'" ';
                }

                $link .= 'rel="'.$rel.'" type="text/css" ';

                if ($media    != '')
                {
                    $link .= 'media="'.$media.'" ';
                }

                if ($title    != '')
                {
                    $link .= 'title="'.$title.'" ';
                }

                $link .= "/>\n";
            }
        }
        else
        {                
            $ext = 'css';
            if(strpos($href, 'js/') === 0)
            {
                $ext  = 'js';
                $href = substr($href, 3);
            }
          
            $href = ltrim($href, '/');  // remove first slash

            if ( strpos($href, '://') !== FALSE)
            {
                $link .= ' href="'.$href.'" ';
            }
            elseif ($index_page === TRUE)
            {
                $link .= ' href="'. $ob->config->site_url($href, false) .'" ';
            }
            else
            {
                $link .= ' href="'. _get_public_path($href, $extra_path, $ext) .'" ';
            }

            $link .= 'rel="'.$rel.'" type="text/css" ';

            if ($media    != '')
            {
                $link .= 'media="'.$media.'" ';
            }

            if ($title    != '')
            {
                $link .= 'title="'.$title.'" ';
            }

            $link .= "/>\n";
        }

        return $link;
    }
}                       
// ------------------------------------------------------------------------

/**
* Build js files in <head> tags
*
* js('welcome.js');
* js('subfolder/welcome.js')
* js('../module/welcome.js');  from /modules dir
* js(array('welcome.js', 'hello.js'));
*
* @author   Ersin Guvenc
* @param    string $src  it can be via a path
* @param    string $arguments
* @param    string $type
* @param    string $index_page load js dynamically
* @version  0.1
* @version  0.2 removed /js dir, added _get_public_path() func.
*
*/
if( ! function_exists('js') )
{
    function js($src, $arguments = '', $type = 'text/javascript', $index_page = FALSE)
    {
        $ob = this();

        $link = '<script type="'.$type.'" ';

        $_ob = lib('ob/Storage');   // obullo changes ..

        // When user use view_set_folder('js', 'iphone'); ..  /public/iphone/css/welcome.css
        $extra_path = '';
        if( isset($_ob->view->js_folder{1}) )
        {
            $extra_path = $_ob->view->js_folder;
        }
        
        if (is_array($src))
        {
            $link = '';

            foreach ($src as $v)
            {
                $link .= '<script type="'.$type.'" ';

                $v = ltrim($v, '/');   // remove first slash  ( Obullo changes )

                if ( strpos($v, '://') !== FALSE)
                {
                    $link .= ' src="'. $v .'" ';
                }
                else
                {
                    $link .= ' src="'. _get_public_path($v, $extra_path) .'" ';
                }

                $link .= "></script>\n";
            }

        }
        else
        {
            $src = ltrim($src, '/');   // remove first slash

            if ( strpos($src, '://') !== FALSE)
            {
                $link .= ' src="'. $src .'" ';
            }
            elseif ($index_page === TRUE)  // .js file as PHP
            {
                $link .= ' src="'. $ob->config->site_url($src, false) .'" ';
            }
            else
            {
                $link .= ' src="'. _get_public_path($src, $extra_path) .'" ';
            }

            $link .= $arguments;
            $link .= "></script>\n";
        }

        return $link;

    }
}

// ------------------------------------------------------------------------

/**
 * Get configured output of the called plugin.
 * 
 * @param string $name plugin name (plugin folder)
 * @param string $config_file name of the plugin config file.
 * @return string
 */
if ( ! function_exists('plugin'))
{
    function plugin($plugin_name, $filename = 'plugins')
    {
        loader::config($filename);  // Load Module or Application plugin from config file.
                                    // Obullo first look at module/config folder if exists
                                    // otherwise it load the file from application/config folder.
        
        $plugins = this()->config->item($plugin_name);
        
        if(count($plugins) == 0)
        {
            return;
        }
        
        $output = '';
        foreach($plugins as $file_path)
        {
            if(strpos(ltrim($file_path), 'js/') === 0)
            {
                $output.= js(substr($file_path, 3));
            }
            
            if(strpos(ltrim($file_path), 'css/') === 0)
            {
                $output.= css(substr($file_path, 3));
            }
        }
        
        return $output;
    }
}

// ------------------------------------------------------------------------

/**
* Generates meta tags from an array of key/values
*
* @access   public
* @param    array
* @return   string
*/
if( ! function_exists('meta') )
{
    function meta($name = '', $content = '', $type = 'name', $newline = "\n")
    {
        // Since we allow the data to be passes as a string, a simple array
        // or a multidimensional one, we need to do a little prepping.
        if ( ! is_array($name))
        {
            $name = array(array('name' => $name, 'content' => $content, 'type' => $type, 'newline' => $newline));
        }
        else
        {
            // Turn single array into multidimensional
            if (isset($name['name']))
            {
                $name = array($name);
            }
        }

        $str = '';
        foreach ($name as $meta)
        {
            $type       = ( ! isset($meta['type']) OR $meta['type'] == 'name') ? 'name' : 'http-equiv';
            $name       = ( ! isset($meta['name']))     ? ''     : $meta['name'];
            $content    = ( ! isset($meta['content']))    ? ''     : $meta['content'];
            $newline    = ( ! isset($meta['newline']))    ? "\n"    : $meta['newline'];

            $str .= '<meta '.$type.'="'.$name.'" content="'.$content.'" />'.$newline;
        }

        return $str;
    }
}

// ------------------------------------------------------------------------

/**
 * Link
 *
 * Generates link to a CSS file
 *
 * @access   public
 * @param    mixed    stylesheet hrefs or an array
 * @param    string   rel
 * @param    string   type
 * @param    string   title
 * @param    string   media
 * @param    boolean  should index_page be added to the css path
 * @return   string
 */
if( ! function_exists('link_tag') )
{
    function link_tag($href = '', $rel = 'stylesheet', $type = '', $title = '', $media = '', $index_page = FALSE)
    {
        $ob = this();

        $link = '<link ';

        if ( strpos($href, '://') !== FALSE)
        {
            $link .= ' href="'.$href.'" ';
        }
        elseif ($index_page === TRUE)
        {
            $link .= ' href="'. $ob->config->site_url($href, false) .'" ';
        }
        else
        {
            $public_path = ' href="'. _get_public_path($href) .'" ';

            if($public_path == FALSE)
            {
                $link .= ' href="'. $ob->config->site_url($href, false) .'" ';
            }
            else
            {
                $link .= $public_path;
            }
        }

        $link .= 'rel="'.$rel.'" ';

        if ($type    != '')
        {
            $link .= 'type="'.$type.'" ';
        }

        if ($media    != '')
        {
            $link .= 'media="'.$media.'" ';
        }

        if ($title    != '')
        {
            $link .= 'title="'.$title.'" ';
        }

        $link .= '/>';

        return $link."\n";
    }
}

// ------------------------------------------------------------------------

/**
* Generates a page document type declaration
*
* Valid options are xhtml11, xhtml-strict, xhtml-trans, xhtml-frame,
* html4-strict, html4-trans, and html4-frame.
*
* Values are saved in the doctypes config file.
*
* @access  public
*/
if( ! function_exists('doctype') )
{
    function doctype($type = 'xhtml1-strict')
    {
        return config_item($type, 'doctypes');
    }
}

// Body TAGS
// ------------------------------------------------------------------------


/**
* Heading
*
* Generates an HTML heading tag.  First param is the data.
* Second param is the size of the heading tag.
*
* @access   public
* @param    string
* @param    integer
* @return   string
*/
if( ! function_exists('heading') ) 
{
    function heading($data = '', $h = '1')
    {
        return "<h".$h.">".$data."</h".$h.">";
    }
}

// ------------------------------------------------------------------------

/**
* Unordered List
*
* Generates an HTML unordered list from an single or multi-dimensional array.
*
* @access   public
* @param    array
* @param    mixed
* @return   string
*/
if( ! function_exists('ul') ) 
{
    function ul($list, $attributes = '')
    {
        return _list('ul', $list, $attributes);
    }
}
 
// ------------------------------------------------------------------------

/**
* Ordered List
*
* Generates an HTML ordered list from an single or multi-dimensional array.
*
* @access   public
* @param    array
* @param    mixed
* @return   string
*/
if( ! function_exists('ol') ) 
{
    function ol($list, $attributes = '')
    {
        return _list('ol', $list, $attributes);
    }
}

// ------------------------------------------------------------------------

/**
* Generates the list
*
* Generates an HTML ordered list from an single or multi-dimensional array.
*
* @access   private
* @param    string
* @param    mixed
* @param    mixed
* @param    intiger
* @return   string
*/
if( ! function_exists('_list') ) 
{
    function _list($type = 'ul', $list = '', $attributes = '', $depth = 0)
    {
        // If an array wasn't submitted there's nothing to do...
        if ( ! is_array($list))
        {
            return $list;
        }

        // Set the indentation based on the depth
        $out = str_repeat(" ", $depth);

        // Were any attributes submitted?  If so generate a string
        if (is_array($attributes))
        {
            $atts = '';
            foreach ($attributes as $key => $val)
            {
                $atts .= ' ' . $key . '="' . $val . '"';
            }
            $attributes = $atts;
        }

        // Write the opening list tag
        $out .= "<".$type.$attributes.">\n";

        // Cycle through the list elements.  If an array is
        // encountered we will recursively call _list()

        static $_last_list_item = '';
        foreach ($list as $key => $val)
        {
            $_last_list_item = $key;

            $out .= str_repeat(" ", $depth + 2);
            $out .= "<li>";

            if ( ! is_array($val))
            {
                $out .= $val;
            }
            else
            {
                $out .= $_last_list_item."\n";
                $out .= _list($type, $val, '', $depth + 4);
                $out .= str_repeat(" ", $depth + 2);
            }

            $out .= "</li>\n";
        }

        // Set the indentation for the closing tag
        $out .= str_repeat(" ", $depth);

        // Write the closing list tag
        $out .= "</".$type.">\n";

        return $out;
    }
}

// ------------------------------------------------------------------------

/**
* Generates HTML BR tags based on number supplied
*
* @access   public
* @param    integer
* @return   string
*/
if( ! function_exists('br') ) 
{
    function br($num = 1)
    {
        return str_repeat("<br />", $num);
    }
}

// ------------------------------------------------------------------------

/**
* Image
*
* Generates an <img /> element
*
* @access   public
* @param    mixed    $src  sources folder image path via filename
* @param    boolean  $index_page
* @param    string   $attributes
* @version  0.1
* @version  0.2      added view_set_folder('img'); support
* @version  0.2      added $attributes variable
* @return   string
*/
if( ! function_exists('img') ) 
{
    function img($src = '', $attributes = '', $index_page = FALSE)
    {
        if ( ! is_array($src) )
        {
            $src = array('src' => $src);
        }

        $_ob = lib('ob/Storage');       // obullo changes ..
                
        $extra_path = '';
        if( isset($_ob->view->img_folder{1}) )  // When user use view_set_folder('img');
        {
            $extra_path = '/' . $_ob->view->img_folder; 
        }
        
        $img = '<img';

        foreach ($src as $k => $v)
        {
            $v = ltrim($v, '/');   // remove first slash
            
            if ($k == 'src' AND strpos($v, '://') === FALSE)
            {
                $ob = this();

                if ($index_page === TRUE)
                {
                    $img .= ' src="'.$ob->config->site_url($v, false).'" ';
                }
                else
                {
                    $img .= ' src="' . _get_public_path($v, 'images'. $extra_path) .'" ';
                }
            }
            else
            {
                $img .= " $k=\"$v\" ";   // for http://
            }
        }

        $img .= $attributes . ' />';

        return $img;
    }
}
// ------------------------------------------------------------------------

/**
 * Generates non-breaking space entities based on number supplied
 *
 * @access   public
 * @param    integer
 * @return   string
 */
if( ! function_exists('nbs') ) 
{
    function nbs($num = 1)
    {
        return str_repeat("&nbsp;", $num);
    }
}

// ------------------------------------------------------------------------ 

/**
* Parse head files to learn whether it
* comes from modules directory.
*
* convert  this path ../welcome/welcome.css
* to /public_url/modules/welcome/public/css/welcome.css
*
* @author   CJ Lazell
* @author   Ersin Guvenc
* @access   private
* @param    mixed $file_url
* @param    mixed $extra_path
* @return   string | FALSE
*/
if( ! function_exists('_get_public_path') )
{
    function _get_public_path($file_url, $extra_path = '', $custom_extension = '')
    {                              
        $OB = this();
        
        $sub_module_path = $GLOBALS['sub_path'];
        
        // $file_url  = strtolower($file_url);
        
        if(strpos($file_url, 'sub.') === 0)   // sub.module/module folder request
        {
            $sub_module_path = ''; // clear sub module path
            
            $paths          = explode('/', $file_url); 
            $filename       = array_pop($paths);       // get file name
            $sub_modulename = array_shift($paths);     // get sub module name
            $modulename     = array_shift($paths);     // get module name
            
            $sub_module_path = $sub_modulename. DS .SUB_MODULES;

        }
        elseif(strpos($file_url, '../') === 0)   // if ../modulename/public folder request 
        {
            $sub_module_path = ''; // clear sub module path
            
            $paths      = explode('/', substr($file_url, 3));
            $filename   = array_pop($paths);          // get file name
            $modulename = array_shift($paths);        // get module name
        }
        else    // if current modulename/public request
        {
            $filename = $file_url;          
            $paths    = array();
            if( strpos($filename, '/') !== FALSE)
            {
                $paths      = explode('/', $filename);
                $filename   = array_pop($paths);
            }

            if(isset($GLOBALS['d']))
            {
                $modulename = $GLOBALS['d'];
            }
            else
            {
                $modulename = lib('ob/Router')->fetch_directory();  
            }
        }

        $sub_path   = '';
        if( count($paths) > 0)
        {
            $sub_path = implode('/', $paths) . '/';      // .module/public/css/sub/welcome.css  sub dir support
        }
                             
        $ext = substr(strrchr($filename, '.'), 1);   // file extension
        if($ext == FALSE) 
        {
            return FALSE;
        }

        if($custom_extension != '') // set like this css('js/folder/theme/ui.css')
        {
            $ext = $custom_extension;
        }
        
        $folder = $ext . '/';
        
        if($extra_path != '')
        {
            $extra_path = trim($extra_path, '/').'/';
            $folder = '';
        }

        $ROOT = str_replace(ROOT, '', rtrim(MODULES .$sub_module_path, DS));
        
        $public_url    = $OB->config->public_url('', true) .str_replace(DS, '/', $ROOT). '/';
        $public_folder = trim($OB->config->item('public_folder'), '/');

        // if config public_folder = 'public/admin' just grab the 'public' word
        // so when managing multi applications user don't need to divide public folder files.

        if( strpos($public_folder, '/') !== FALSE)
        {
            $public_folder = current(explode('/', $public_folder));
        }                                                         

        // example
        // .backend/modules/welcome/public/css/welcome.css    (public/css/welcome.css) [backend] removed
        // .fronted/modules/welcome/public/css/welcome.css

        $pure_path      = $modulename .'/'. $public_folder .'/'. $extra_path . $folder . $sub_path . $filename;
        $full_path      = $public_url . $pure_path;
        $app_public_url = $public_folder .'/' . $extra_path . $folder . $sub_path . $filename;
        
        // if file not exists in current module folder fetch it from app /public folder. 
        
        if( is_readable(MODULES . $sub_module_path. str_replace('/', DS, trim($pure_path, '/'))) ) 
        {         
            return $full_path;
        }
        elseif(is_readable(ROOT . str_replace('/', DS, trim($app_public_url, '/'))))
        {
            return $OB->config->public_url('', true) . $app_public_url;
        }
        else
        {
            log_me('debug', 'File not exist or the path ' . $full_path .' is not readable or you need to check your chmod settings !');
        }
        
        return $full_path;
    }
}


/* End of file html.php */
/* Location: ./obullo/helpers/html.php */
