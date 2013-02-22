<?php
require_once '../../../trunk/arraise.php';

$arraise = new arraise('blog', array('savePath' => 'C:\\public_html\\arraise\\trunk\\data'));

$posts = $arraise->select('posts', null, null, 'date DESC');
?>
<h1>Blog</h1>
<?php
foreach ($posts as $post) {
    echo '<fieldset>';
    echo "<legend>{$post['subject']} (".date('d/m/Y', $post['date']).")";
    echo " - <a href=\"editpost.php?id={$post['id']}\">Edit</a>";
    echo nl2br($post['body']);
    echo '</fieldset>';
}
?>