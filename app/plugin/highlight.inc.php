<?php
define(PLUGIN_HIGHLIGHT_URI, (defined('SKIN_URI') ? SKIN_URI : SKIN_DIR) . 'SyntaxHighlighter/');

function plugin_highlight_init()
{
	global $head_tags;

	$head_tags[] = '<link rel="stylesheet" href="//cdn.jsdelivr.net/gh/highlightjs/cdn-release@9.15.8/build/styles/monokai-sublime.min.css">';
	$head_tags[] = '<script src="//cdn.jsdelivr.net/gh/highlightjs/cdn-release@9.15.8/build/highlight.min.js"></script>';
}

function plugin_highlight_convert()
{
	global $head_tags;

	static $languages = array();

	$args   = func_get_args();
	$end    = in_array("end", $args);
	$body   = array_pop($args);
	$class  = array_shift($args);
	list($language) = explode(':', $class);

	$ret = '';
	if (!$end) {
		// for highlight.js
		$ret .= '<div class="for_highlight">';
		$ret .= '<pre><code class="'. ' ' . htmlspecialchars($language). '">';
		$ret .= htmlspecialchars($body);
		$ret .= '</code></pre>';
		$ret .= '</div>';
	}

	if ($end) {
		$tags = array();
		$tags[] = '<script type="text/javascript">';

		$tags[] = <<<EOF
//タブスペースの調整
hljs.configure({
	tabReplace: "    ",	//4文字分の半角スペース
});
EOF;

		$tags[] = 'hljs.initHighlightingOnLoad();';
		$tags[] = '</script>';

		return implode($tags, "\n");

	}
	return $ret;
}
