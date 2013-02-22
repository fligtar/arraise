<?php
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is arraise.
 *
 * The Initial Developer of the Original Code is
 * Justin Scott <fligtar@gmail.com>.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */

if (!empty($_GET['file'])) {
    $file = 'files/'.$_GET['file'];
    if (file_exists($file)) {
        $dblink = mysql_connect('localhost', 'username', 'password');
        mysql_select_db('tracking');
        
        $query = mysql_query("SELECT * FROM track WHERE id='{$_GET['file']}'");
        
        if (mysql_num_rows($query) == 0) {
            mysql_query("INSERT INTO track (id, hits) VALUES('{$_GET['file']}', '1')");
            $hits = 1;
        } else {
            $array = mysql_fetch_array($query);
            $hits = $array['hits'] + 1;
            mysql_query("UPDATE track SET hits=hits+1 WHERE id='{$_GET['file']}'");
        }
        mysql_close($dblink);
        
        $fp = fopen("tracking/{$_GET['file']}.txt", "a");
        fwrite($fp, "$hits.\t".date("r")."\t".$_SERVER["REMOTE_ADDR"]."\t".gethostbyaddr($_SERVER["REMOTE_ADDR"])."\t".$_SERVER["HTTP_REFERER"]."\t".$_SERVER["HTTP_USER_AGENT"]."\n");
        fclose($fp);
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: attachment; filename=' . $_GET['file']);
        readfile($file);
        exit;   
    }
}
?>
<div align="center">
    <img src="images/arraise-med.png"><br><br>
    <h1>File not found.</h1>
    <h3><a href="http://arraise.fligtar.com/wiki/Downloads">Go to Downloads</a></h3>
</div>