<?php
require_once '../../../trunk/arraise.php';

$arraise = new arraise('blog', array('savePath' => 'C:\\public_html\\arraise\\trunk\\data'));

$id = $_REQUEST['id'];
$post = $arraise->select('posts', null, array('id' => $id));

if (!empty($_POST['edit'])) {
    $data = array(
                  'subject' => $_POST['subject'],
                  'body' => $_POST['body']
                 );
    
    if ($arraise->update('posts', $data, array('id' => $id), 1)) {
        echo "<h3>Post {$id} updated!</h3>";
    }
    else {
        echo "<h3>There was an error updating your post: {$arraise->lastError}</h3>";
    }
}
elseif (!empty($_POST['delete'])) {
    if ($arraise->delete('posts', array('id' => $id), 1)) {
        echo "<h3>Post {$id} deleted!</h3>";
    }
    else {
        echo "<h3>There was an error deleting your post: {$arraise->lastError}</h3>";
    }
}
?>
<a href="index.php">Back to blog...</a><br>
<h1>Edit Post <?=$id?></h1>
<form method="post">
<input type="hidden" name="id" value="<?=$id?>">
Subject: <input type="text" name="subject" size=70 value="<?=$post[$id]['subject']?>"><br>
<textarea name="body" rows=10 cols=70><?=$post[$id]['body']?></textarea><br><br>
<input type="submit" name="edit" value="Edit Post">
<input type="submit" name="delete" value="Delete Post">
</form>