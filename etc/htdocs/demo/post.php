<?php
require_once '../../../trunk/arraise.php';

if (!empty($_POST['body'])) {
    $arraise = new arraise('blog', array('savePath' => 'C:\\public_html\\arraise\\trunk\\data'));
    
    $data = array(
                  'subject' => $_POST['subject'],
                  'body' => $_POST['body'],
                  'date' => time()
                 );
    
    if ($arraise->insert('posts', $data)) {
        echo "<h3>Post {$arraise->lastInsertedId} created!</h3>";
    }
    else {
        echo "<h3>There was an error creating your post: {$arraise->lastError}</h3>";
    }
}
?>
<a href="index.php">Back to blog...</a><br>
<h1>New Post</h1>
<form method="post">
Subject: <input type="text" name="subject" size=70><br>
<textarea name="body" rows=10 cols=70></textarea><br><br>
<input type="submit" value="Submit Post">
</form>