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
    function css($href, $title = '', $media = '', $rel = 'stylesheet', $index_page = FALSE)
    {
        $ob = this();
        $extension = '.css';
        
        $link = '<link ';

        $vi = Ssc::instance();   // obullo changes ..

        // When user use view_set_folder('css', 'iphone'); ..  /public/iphone/css/welcome.css
        $path = '';
        if(isset($vi->_ew->css_folder{1}))
        {
            $path = $vi->_ew->css_folder .'/';
        }

        if (is_array($href))
        {   
            $link = '';
            
            foreach ($href as $v)
            {
                $link .= '<link ';
                
                $v = ltrim($v, '/');   // remove first slash  ( Obullo changes )
                
                if ( strpos($v, '://') !== FALSE)
                {
                    $link .= ' href="'. $v . $extension .'" ';
                }
                else
                {
                    $link .= _fetch_head_file($v, $extension, 'css/', ' href="', '" ', $path);   
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
                $link .= _fetch_head_file($href, $extension, 'css/', ' href="', '" ', $path);  // is .css file from /modules dir ?
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

            $link .= '/>';
        }

        return $link;
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
                    $link .= _fetch_head_file($v, $extension, 'js/', ' src="', '" ');   
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
                $link .= _fetch_head_file($src, $extension, 'js/', ' src="', '" ');  // is .js file from /modules dir ?
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
                if(strpos($href, '.') !== FALSE)
                {
                    $part = explode('.', $href);  // if url has extension like .rss
                    print_r($part); exit;
                    $link .= _fetch_head_file($href, '', 'rss/', ' href="', '" ');  // is .extension file from /modules dir ?
                } 
                else
                {
                    $link .= ' href="'.$href.'" ';
                }
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