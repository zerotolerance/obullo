<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 * 
 * @package         obullo       
 * @author          obullo.com
 * @copyright       Ersin Guvenc (c) 2010.
 * @filesource
 * @license
 */ 
 
/**
 * Obullo Head Tag Helper
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Language
 * @author      Ersin Guvenc
 * @version     0.1
 * @version     0.2 added script functions
 * @link        
 */
// --------------------------------------------------------------------

if( ! isset($_head->_tag))  // Helper Constructror
{
    $_head = Ssc::instance();
    $_head->_tag = new stdClass();

    $_head->_tag->arr_store = array();
                      
    log_me('debug', "Head_tag Helper Initialized");
}

// --------------------------------------------------------------------

/**
* Build css files in <head> tags
* 
* @author   Ersin Guvenc
* @param    mixed   $filename array or string
* @param    string  $title
* @param    string  $media  'all' or 'print' etc..
* @version  0.1
* @version  0.2 added $path variable
* @version  0.2 added _ent->css_folder variable
* @version  0.3 depreciated $path param
* @return   string
*/
if( ! function_exists('css') ) 
{
    function css($filename, $title = '', $media = '', $rel = 'stylesheet', $index_page = FALSE)
    {
        $link = link_tag($filename, $rel, 'text/css', $title, $media, $index_page);
        
        if($link) return $link."\n";
    }   
}
// ------------------------------------------------------------------------

/**
* Build js files in <head> tags
* 
* @author   Ersin Guvenc
* @param    string $filename  it can be via a path
* @param    string $arguments
* @param    string $type
* @version  0.1
* @version  0.2 removed /js dir 
* 
*/
if( ! function_exists('js') ) 
{
    function js($src, $arguments = '', $type = 'text/javascript', $index_page = FALSE)
    {        
        $ob = this();
        $extension = '.js';
        
        $link = '<script type="'.$type.'" '; 
        
        if (is_array($src))
        {
            $link = '';
            
            foreach ($src as $v)
            {
                $link .= '<script type="'.$type.'" '; 
                
                $v = ltrim($v, '/');   // remove first slash  ( Obullo changes )
                
                if ( strpos($v, '://') !== FALSE)
                {
                    $link .= ' src="'. $v . $extension .'" ';
                }
                else
                {
                    $path = _get_path($href, $type);                
                
                    $link .= ' src="'. $path .'" ';
                
                    // $link .= _fetch_head_file($v, $extension, 'js/', ' src="', '" ');   
                }
        
                $link .= "></script>\n";        
            }

        }
        else
        {
            $src = ltrim($src, '/');   // remove first slash
            
            if ( strpos($src, '://') !== FALSE)
            {
                $link .= ' src="'. $src . $extension .'" ';
            }
            elseif ($index_page === TRUE)  // .js file as PHP
            {
                $link .= ' src="'. $ob->config->site_url($src) .'" ';
            }
            else
            {
                $path = _get_path($href, $type);                
                if( ! $path) return;
                $link .= ' src="'. $path .'" ';
                
                // $link .= _fetch_head_file($src, $extension, 'js/', ' src="', '" ');  // is .js file from /modules dir ?
            }
                
            $link .= $arguments;
            $link .= "></script>\n";
        }
        
        return $link;
        
    }
}

// ------------------------------------------------------------------------ 

/**
* Parse head files to learn whether it
* comes from modules directory.
* 
* !!!! @deprecated !!!!
* 
* @author   CJ Lazell
* @access   private
* @param    mixed $url
* @param    mixed $extension
* @param    mixed $folder
* @param    mixed $prefix
* @param    mixed $suffix
* @param    mixed $path
* 
* @return   string
*/
function _fetch_head_file($url, $extension = '.js', $folder = 'js/', $prefix = ' src="', $suffix = '" ', $path = '')
{
    echo $folder;
    
    if(strpos($url, '../') === 0)   // If Module request ?
    {
        // convert css(../welcome/welcome)  to ../modules/welcome/public/css/welcome.css
        $link = this()->config->base_url().trim(DIR, DS).'/';
        $link.= preg_replace('/(\w+)\/(.+)/i', '$1/public/'.$folder.'$2', substr($url, 3));
    }
    else
    {
        $link = this()->config->public_url() . $path . $folder . $url;
    }
    
    return  $prefix. $link . $extension . $suffix;
} 

// ------------------------------------------------------------------------ 

/**
* Load inline script file from
* local folder.
* 
* @param string $filename
* @param array  $data
*/
if( ! function_exists('script') ) 
{
    function script($filename = '', $data = '')
    {
        return _load_script(DIR .$GLOBALS['d']. DS .'scripts'. DS, $filename, $data);
    }
}
// ------------------------------------------------------------------------ 

/**
* Load inline script file from
* application folder.
* 
* @param string $filename
* @param array  $data
*/
if( ! function_exists('script_app') ) 
{
    function script_app($filename = '', $data = '')
    {
        return _load_script(APP .'scripts'. DS, $filename, $data);
    }
}
// ------------------------------------------------------------------------ 

/**
* Load inline script file from
* base folder.
* 
* @param string $filename
* @param array  $data
*/
if( ! function_exists('script_base') ) 
{
    function script_base($filename = '', $data = '')
    {
        return _load_script(BASE .'scripts'. DS, $filename, $data);
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
    function link_tag($href = '', $rel = 'stylesheet', $type = 'text/css', $title = '', $media = '', $index_page = FALSE)
    {
        $ob = this();

        $link = '<link ';
        
        if (is_array($href))
        {
            foreach ($href as $k => $v)
            {
                $v = ltrim($v, '/');   // remove first slash

                if ($k == 'href' AND strpos($v, '://') === FALSE)
                {
                    if ($index_page === TRUE)
                    {
                        $link .= ' href="'.$ob->config->site_url($v).'" ';
                    }
                    else
                    {
                        $link .= ' href="'.$ob->config->public_url(). $v.'" ';
                    }
                }
                else
                {
                    $link .= "$k=\"$v\" ";
                }
            }

            $link .= "/>";
        }
        else
        {
            $href = ltrim($href, '/');  // remove first slash

            if ( strpos($href, '://') !== FALSE)
            {
                $link .= ' href="'.$href.'" ';
            }
            elseif ($index_page === TRUE)
            {
                $link .= ' href="'. $ob->config->site_url($href) .'" ';
            }
            else
            {
                $extra_path = '';
                if($type == 'text/css')   // When user use view_set_folder('css', 'iphone');
                {
                     // add extra path  ..  /public/iphone/css/welcome.css
                    if(isset($vi->_ew->css_folder{1}))
                    {
                        $extra_path = $vi->_ew->css_folder .'/';
                    }
                }
                
                $path = _get_path($href, $type, $title, $media, $extra_path);
                
                $link .= ' href="'. $path .'" ';
            }

            $link .= 'rel="'.$rel.'" type="'.$type.'" ';

            if ($media    != '')
            {
                $link .= 'media="'.$media.'" ';
            }

            if ($title    != '')
            {
                $link .= 'title="'.$title.'" ';
            }

            $link .= '/>';
        }

        return $link;
    }
}

// ------------------------------------------------------------------------

/**
* get_path
*
* Gets the correct path of the files
*
* @author   CJ Lazell
* @param    mixed $file_name
* @param    mixed $type
* @param    string $title
* @param    string $media
* @param    string $extra_path  we need it for view_set_folder()
* 
* @version  0.2  added $extra_path parameter 
* 
* @access   public
* @return   void
*/
if( ! function_exists('_get_path') )
{
    function _get_path($file_name, $type, $title= '', $media= '', $extra_path = '')
    {
        $_head = Ssc::instance();
        $_head->_tag->arr_store = array();
        
        $ob = this();
        
        switch ($type) 
        {
            case 'text/javascript':
             $folder    = 'js/';
             $extension = "js";
             $name      = '';
             break;
             
            case 'text/css':
             $folder    = 'css/';
             $extension = "css";
             $name      = 'stylesheet';
             break;
             
            case 'application/rss+xml':
             $folder    = 'rss/';
             $extension = "rss";
             $name      = '';
             break;
           
            default:
             $folder    = '';
             $extension = '';
             $name      = '';
        }

        if(strpos($file_name, '../') === 0)
        {
            $path = preg_replace('/(\w+)\/(.+)/i', '$1/public/'. $extra_path . $folder.'$2', substr($file_name, 3));
        } 
        else 
        {
            $path = $ob->config->public_url() . $extra_path . $folder . $file_name;

            if(strpos($file_name, '*'))
            {
                $path     = substr($path, 0, -2);
                $location = substr($file_name, 0, -2);
                
                $files  = _grab_files(FPATH .$path. DS, '', array($extension), strpos($file_name, '**') ? TRUE : FALSE);

                foreach($files as $file)
                {
                      $file= str_replace(".$extension", '', substr($file, 1));
                      
                      if($extension != 'js') 
                      {
                          echo link_tag($location . DS . $file, $name, $type, $title, $media)."\n";
                      }
                      else
                      {
                          echo js($location . DS . $file)."\n";
                      }
                }
                  
                return;
            }

            return $path .'.'.$extension;
         }

   }
   
}

// ------------------------------------------------------------------------

/**
* _grab_files
*
* Searches the directory and sub directories for your files
*
* @author   CJ Lazell
* @access   private
* @param    mixed $dir
* @param    string $baseDir
* @param    mixed $types
* @param    mixed $recursive
* @access   public
* @return   void
*/
if( ! function_exists('_grab_files') )
{
    function _grab_files($dir, $base_dir = '', $types = NULL, $recursive = TRUE) 
    {
        $_head = Ssc::instance();
        
        if ($dh = opendir($dir)) 
        {
            while (($file = readdir($dh)) !== FALSE) 
            {
                if ($file === '.' || $file === '..' || preg_match("/(?<!.)\..+/i",$file)) 
                {
                    continue;
                }
                
                if (is_file($dir . $file) AND ! preg_match("/\.\_.+/i",$file)) 
                {
                    if (is_array($types)) 
                    {
                        if ( ! in_array(strtolower(pathinfo($dir . $file, PATHINFO_EXTENSION)), $types, TRUE)) 
                        {
                            continue;
                        }
                    }
                  
                    $_head->_tag->arr_store[] = $base_dir .'/'. $file;
                    
                }
                elseif($recursive AND is_dir($dir . $file)) 
                {
                    _grab_files($dir . $file . DIRECTORY_SEPARATOR, $base_dir .'/'. $file, $types, $recursive);
                }
            }
            
            closedir($dh);
        }
        
        sort($_head->_tag->arr_store);
        
        return $_head->_tag->arr_store;
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

/* End of file head_tag.php */
/* Location: ./base/helpers/head_tag.php */