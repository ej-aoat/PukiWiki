<?php

/**
 * ajaxtree.inc.php - List pages as an Ajax tree menu
 *
 * @author     revulo
 * @licence    http://www.gnu.org/licenses/gpl-2.0.html  GPLv2
 * @version    1.3
 * @link       http://www.revulo.com/PukiWiki/Plugin/AjaxTree.html
 */

// Directory path of cache files
if (!defined('PLUGIN_AJAXTREE_HTML_DIR')) {
    define('PLUGIN_AJAXTREE_HTML_DIR', 'html/ajaxtree/');
}

// Check the mtime of wiki directory
if (!defined('PLUGIN_AJAXTREE_CHECK_MTIME')) {
    define('PLUGIN_AJAXTREE_CHECK_MTIME', true);
}

// Display the number of descendant pages
if (!defined('PLUGIN_AJAXTREE_COUNT_DESCENDANTS')) {
    define('PLUGIN_AJAXTREE_COUNT_DESCENDANTS', true);
}

// Move FrontPage to the top of the tree
if (!defined('PLUGIN_AJAXTREE_TOP_DEFAULTPAGE')) {
    define('PLUGIN_AJAXTREE_TOP_DEFAULTPAGE', true);
}

// Hide top-level leaf pages such as Help, MenuBar etc.
if (!defined('PLUGIN_AJAXTREE_HIDE_TOPLEVEL_LEAVES')) {
    define('PLUGIN_AJAXTREE_HIDE_TOPLEVEL_LEAVES', true);
}

// Sort type ('name' or 'reading')
if (!defined('PLUGIN_AJAXTREE_SORT_TYPE')) {
    define('PLUGIN_AJAXTREE_SORT_TYPE', 'name');
}

// Ignore list
if (!defined('PLUGIN_AJAXTREE_NON_LIST')) {
    define('PLUGIN_AJAXTREE_NON_LIST', '');
}

// Include list
if (!defined('PLUGIN_AJAXTREE_INCLUDE_LIST')) {
    define('PLUGIN_AJAXTREE_INCLUDE_LIST', '');
}

// Expand list
if (!defined('PLUGIN_AJAXTREE_EXPAND_LIST')) {
    define('PLUGIN_AJAXTREE_EXPAND_LIST', '');
}


function plugin_ajaxtree_init()
{
    $messages['_ajaxtree_messages'] = array(
        'title'   => 'AjaxTree',
        'toppage' => 'Top'
    );
    set_plugin_messages($messages);
}

function plugin_ajaxtree_action()
{
    plugin_ajaxtree_reset_cache();

    return array(
        'msg'  => 'AjaxTree',
        'body' => 'Cache is updated.'
    );
}

function plugin_ajaxtree_convert()
{
    global $vars;

    $html = plugin_ajaxtree_get_html();
    plugin_ajaxtree_modify_html($html, $vars['page']);
    return $html;
}

function plugin_ajaxtree_get_html()
{
    global $_ajaxtree_messages;

    $file       = plugin_ajaxtree_get_cachename('/');
    $skin_url   = defined('SKIN_URI') ? SKIN_URI : SKIN_DIR;
    $javascript = $skin_url . 'ajaxtree/ajaxtree.js';

    $html = '<div id="ajaxtree">' . "\n"
          . '<h5>' . htmlspecialchars($_ajaxtree_messages['title']) . '</h5>' . "\n"
          . plugin_ajaxtree_read_file($file) . "\n"
          . '</div>' . "\n"
          . '<script type="text/javascript" src="' . $javascript . '"></script>' . "\n";

    return $html;
}

function plugin_ajaxtree_modify_html(&$html, $current)
{
    global $vars;

    $ancestors   = plugin_ajaxtree_get_ancestors($current);
    $ancestors[] = $current;

    $pos = 0;
    foreach ($ancestors as $ancestor) {
        $search = '<a title="' . htmlspecialchars($ancestor) . '"';
        $pos    = strpos($html, $search, $pos);
        if ($pos === false) {
            continue;
        }

        if ($ancestor === $vars['page']) {
            $search  = '>';
            $pos2    = strpos($html, $search, $pos) + 1;
            $search  = '</a>';
            $pos3    = strpos($html, $search, $pos2);
            $str     = substr($html, $pos2, $pos3 - $pos2);
            $replace = '<span class="current">' . $str . '</span>';
            $length  = $pos3 - $pos + strlen($search);
            $html    = substr_replace($html, $replace, $pos, $length);
        }

        $search = 'collapsed';
        $length = strlen($search);
        $start  = $pos - 2 - $length;

        if (substr($html, $start, $length) === $search) {
            $replace = 'expanded';
            $html    = substr_replace($html, $replace, $start, $length);

            $file    = plugin_ajaxtree_get_cachename($ancestor);
            $search  = '</li>';
            $replace = '<ul>' . plugin_ajaxtree_read_file($file) . '</ul>';
            $pos     = strpos($html, $search, $pos);
            $html    = substr_replace($html, $replace, $pos, 0);
        }
    }
}

function plugin_ajaxtree_get_cachename($page)
{
    if (SOURCE_ENCODING != 'UTF-8' && SOURCE_ENCODING != 'ASCII') {
        $page = mb_convert_encoding($page, 'UTF-8');
    }
    return PLUGIN_AJAXTREE_HTML_DIR . encode($page) . '.html';
}

function plugin_ajaxtree_write_after()
{
    global $vars;

    plugin_ajaxtree_init();

    if ($vars['plugin'] == 'rename') {
        plugin_ajaxtree_reset_cache();
        return;
    }

    $current = $vars['page'];

    if (PLUGIN_AJAXTREE_CHECK_MTIME) {
        $file = get_filename($current);
        if (filemtime($file) > filemtime(DATA_DIR)) {
            return;
        }
    }

    if (PLUGIN_AJAXTREE_COUNT_DESCENDANTS) {
        $ancestors   = plugin_ajaxtree_get_ancestors($current);
        $ancestors[] = '/';
    } else {
        $pos    = strrpos($current, '/');
        $parent = $pos ? substr($current, 0, $pos) : '/';

        if (PLUGIN_AJAXTREE_HIDE_TOPLEVEL_LEAVES && strpos($parent, '/') === false) {
            $ancestors = array($parent, '/');
        } else {
            $ancestors = array($parent);
        }
    }

    foreach ($ancestors as $ancestor) {
        plugin_ajaxtree_update_cache($ancestor);
    }
}

function plugin_ajaxtree_reset_cache()
{
    $pages =& plugin_ajaxtree_get_pages();
    $leaf  =& plugin_ajaxtree_get_leaf_flags();

    foreach ($pages as $page) {
        if ($leaf[$page] === false) {
            plugin_ajaxtree_update_cache($page);
        }
    }
    plugin_ajaxtree_update_cache('/');
}

function plugin_ajaxtree_update_cache($page)
{
    $file = plugin_ajaxtree_get_cachename($page);
    if ($page == '/') {
        $html = plugin_ajaxtree_get_root_html();
    } else {
        $html = plugin_ajaxtree_get_subtree($page);
    }
    plugin_ajaxtree_write_file($file, $html);
}

function plugin_ajaxtree_get_root_html()
{
    global $defaultpage, $_ajaxtree_messages;

    if (PLUGIN_AJAXTREE_COUNT_DESCENDANTS) {
        $counts =& plugin_ajaxtree_get_counts();
    } else {
        $counts = array();
    }

    $html = '';
    if (PLUGIN_AJAXTREE_TOP_DEFAULTPAGE) {
        $url     = plugin_ajaxtree_get_script_uri();
        $title   = htmlspecialchars($defaultpage);
        $s_label = htmlspecialchars($_ajaxtree_messages['toppage']);
        $count   = isset($counts[$defaultpage]) ? ' <span class="count">(' . $counts[$defaultpage] . ')</span>' : '';
        $html    = '<a title="' . $title . '" href="' . $url . '">' . $s_label . $count . '</a>' . "\n";
    }

    $html .= '<ul>' . plugin_ajaxtree_get_subtree('/') . '</ul>';

    if (PLUGIN_AJAXTREE_EXPAND_LIST !== '') {
        $leaf  =& plugin_ajaxtree_get_leaf_flags();
        $pages =& plugin_ajaxtree_get_pages();
        $pages =  preg_grep('/' . PLUGIN_AJAXTREE_EXPAND_LIST . '/', $pages);
        sort($pages, SORT_STRING);

        foreach ($pages as $page) {
            if ($leaf[$page] === false) {
                plugin_ajaxtree_modify_html($html, $page);
            }
        }
    }

    return $html;
}

function plugin_ajaxtree_get_subtree($current)
{
    $pages  =& plugin_ajaxtree_get_children($current);
    $leaf   =& plugin_ajaxtree_get_leaf_flags();
    $script =  plugin_ajaxtree_get_script_uri();

    if (PLUGIN_AJAXTREE_COUNT_DESCENDANTS) {
        $counts =& plugin_ajaxtree_get_counts();
    } else {
        $counts = array();
    }

    $depth   = substr_count($pages[0], '/');
    $indents = str_repeat(' ', $depth);
    if ($depth === 0) {
        $offset = 0;
    } else {
        $offset = strrpos($pages[0], '/') + 1;
    }

    $html = '';
    foreach ($pages as $page) {
        $title   = htmlspecialchars($page);
        $url     = $script . '?' . rawurlencode($page);
        $label   = substr($page, $offset);
        $s_label = htmlspecialchars($label);
        $count   = isset($counts[$page]) ? ' <span class="count">(' . $counts[$page] . ')</span>' : '';

        $html .= "\n" . $indents;
        $html .= ($leaf[$page] === true) ? '<li>' : '<li class="collapsed">';
        $html .= '<a title="' . $title . '" href="' . $url . '">' . $s_label . $count . '</a></li>';
    }

    return $html;
}

function plugin_ajaxtree_get_script_uri()
{
    static $script = null;

    if ($script === null) {
        $script = get_script_uri();
        $script = './' . substr($script, strrpos($script, '/') + 1);
    }
    return $script;
}

function &plugin_ajaxtree_get_leaf_flags()
{
    static $leaf = array();

    if ($leaf === array()) {
        $pages = get_existpages();
        foreach ($pages as $page) {
            if (isset($leaf[$page])) {
                continue;
            }
            $leaf[$page] = true;

            while (($pos = strrpos($page, '/')) !== false) {
                $page  = substr($page, 0, $pos);
                $isset = isset($leaf[$page]);
                $leaf[$page] = false;
                if ($isset === true) {
                    break;
                }
            }
        }
    }
    return $leaf;
}

function plugin_ajaxtree_get_ancestors($current)
{
    $ancestors = array();
    $tokens    = explode('/', $current);
    $depth     = count($tokens);

    if ($depth > 1) {
        $ancestors[0] = $tokens[0];
        for ($i = 1; $i < $depth - 1; ++$i) {
            $ancestors[$i] = $ancestors[$i - 1] . '/' . $tokens[$i];
        }
    }
    return $ancestors;
}

function &plugin_ajaxtree_get_children($current = null)
{
    static $children = array();

    if ($children === array()) {
        $pages =& plugin_ajaxtree_get_pages();
        foreach ($pages as $page) {
            $pos    = strrpos($page, '/');
            $parent = $pos ? substr($page, 0, $pos) : '/';
            $children[$parent][] = $page;
        }
    }

    if ($current === null) {
        return $children;
    }

    plugin_ajaxtree_sort_pages($children[$current]);
    return $children[$current];
}

function &plugin_ajaxtree_get_counts()
{
    global $defaultpage;
    static $counts = array();

    if ($counts === array()) {
        $pages =& plugin_ajaxtree_get_children();
        foreach ($pages as $page => $children) {
            $count = count($children);
            $counts[$page] += $count;
            while (($pos = strrpos($page, '/')) !== false) {
                $page = substr($page, 0, $pos);
                $counts[$page] += $count;
            }
            if (PLUGIN_AJAXTREE_TOP_DEFAULTPAGE) {
                $counts[$defaultpage] += $count;
            }
        }
    }
    return $counts;
}

function &plugin_ajaxtree_get_pages()
{
    static $pages = null;

    if ($pages === null) {
        $pages = get_existpages();
        plugin_ajaxtree_filter_pages($pages);
    }
    return $pages;
}

function plugin_ajaxtree_filter_pages(&$pages)
{
    global $defaultpage, $non_list;

    if (PLUGIN_AJAXTREE_TOP_DEFAULTPAGE) {
        $key = encode($defaultpage) . '.txt';
        unset($pages[$key]);
    }

    if (PLUGIN_AJAXTREE_INCLUDE_LIST !== '') {
        $includes = preg_grep('/' . PLUGIN_AJAXTREE_INCLUDE_LIST . '/', $pages);
    } else {
        $includes = array();
    }

    if (PLUGIN_AJAXTREE_HIDE_TOPLEVEL_LEAVES) {
        $leaf =& plugin_ajaxtree_get_leaf_flags();
        foreach ($pages as $key => $page) {
            if (strpos($page, '/') === false && $leaf[$page] === true) {
                unset($pages[$key]);
            }
        }
    }

    if (PLUGIN_AJAXTREE_NON_LIST !== '') {
        $pattern = '/(?:' . $non_list . ')|(?:' . PLUGIN_AJAXTREE_NON_LIST . ')/';
    } else {
        $pattern = '/' . $non_list . '/';
    }
    if (version_compare(PHP_VERSION, '4.2.0', '>=')) {
        $pages = preg_grep($pattern, $pages, PREG_GREP_INVERT);
    } else {
        $pages = array_diff($pages, preg_grep($pattern, $pages));
    }

    if ($includes) {
        $pages += $includes;
    }
}

function plugin_ajaxtree_sort_pages(&$pages)
{
    switch (PLUGIN_AJAXTREE_SORT_TYPE) {
        case 'reading':
            $readings = plugin_ajaxtree_get_readings($pages);
            asort($readings, SORT_STRING);
            $pages = array_keys($readings);
            break;

        case 'name':
        default:
            sort($pages, SORT_STRING);
            break;
    }
}

function plugin_ajaxtree_get_readings($pages)
{
    static $all_readings;

    if (empty($all_readings)) {
        $all_readings = get_readings();
    }

    $readings = array();
    foreach ($pages as $page) {
        $readings[$page] = $all_readings[$page];
    }
    return $readings;
}

function plugin_ajaxtree_read_file($filename)
{
    $fp = fopen($filename, 'rb');
    if ($fp === false) {
        return false;
    }
    $data = fread($fp, filesize($filename));
    fclose($fp);
    return $data;
}

function plugin_ajaxtree_write_file($filename, $data)
{
    $fp = fopen($filename, file_exists($filename) ? 'r+b' : 'wb');
    if ($fp === false) {
        return false;
    }
    flock($fp, LOCK_EX);
    rewind($fp);
    $bytes = fwrite($fp, $data);
    fflush($fp);
    ftruncate($fp, ftell($fp));
    fclose($fp);
    return $bytes;
}

?>
