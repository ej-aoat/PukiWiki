<?php

error_reporting(0);

define('CHARSET',  'UTF-8');
define('HTML_DIR', 'html/ajaxtree/');

if (isset($_GET['url']) && strpos($_GET['url'], '/') === false) {
    header('Content-Type: text/html; charset=' . CHARSET);
    readfile(HTML_DIR . $_GET['url']);
}
