<?php
// exlink.inc.php,v 1.0 2004/11/09 ari-

function plugin_exlink_convert()
{
	$scr = <<<EOD
<script language="javascript">
<!--
function external_link(){
   var host_Name = location.host;
   var host_Check;
   var link_Href;

   for(var i=0; i < document.links.length; ++i)
   {
       link_Href = document.links[i].host;
       host_Check = link_Href.indexOf(host_Name,0);

       if(host_Check == -1){
           document.links[i].innerHTML = document.links[i].innerHTML + "<img src=\"image/external_link.gif\" height=\"11px\" width=\"11px\" alt=\"[ŠO•”ƒŠƒ“ƒN]\" class=\"external_link\">";
       }

   }
}
window.onload = external_link;
-->
</script><noscript></noscript>
EOD;
	return $scr;
}
