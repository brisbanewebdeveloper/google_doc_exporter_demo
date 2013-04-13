<?php

ini_set('display_errors', 1);

session_start();

require_once 'google_doc_exporter/google_doc_exporter.php';

$thisPage = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

$s =& $_SESSION;

if (isset($_REQUEST['reset'])) {
    unset($_REQUEST['step']);
    unset($s);
}

if ( ! isset($s['clientId'])) $s['clientId'] = '';
if (isset($_POST['clientId'])) $s['clientId'] = $_POST['clientId'];
if ( ! isset($s['clientSecret'])) $s['clientSecret'] = '';
if (isset($_POST['clientSecret'])) $s['clientSecret'] = $_POST['clientSecret'];
if ( ! isset($s['accessToken'])) $s['accessToken'] = false;
$accessToken =& $s['accessToken'];

$content = array(
    'title' => 'Google Doc Exporter Demo',
    'body' => '',
);

$cssMessage = array();
$message = '';

$form_1 =<<<EOH
<form id="gdoc-info" method="post" action="index.php" class="span12">
<p>
    This page displays list of your Google Docs and displays it.
</p>
<p>
    You need to setup <a target="_blank" href="https://code.google.com/apis/console/">Google Console</a> to get your Client ID and Client secret.
</p>
<p>Screenshots for settings:</p>
<ul>
    <li><a target="_screen_shot_1" href="http://note.io/XUPVkJ">http://note.io/XUPVkJ</a></li>
    <li><a target="_screen_shot_2" href="http://note.io/ZCRxyS">http://note.io/ZCRxyS</a></li>
</ul>
<input type="text" id="clientId" class="input-block-level" name="clientId" placeholder="Client ID" value="{$s['clientId']}" />
<input type="text" id="clientSecret" class="input-block-level" name="clientSecret" placeholder="Client secret" value="{$s['clientSecret']}" />
<input type="submit" class="btn" value="Authenticate" />
<input type="hidden" name="step" value="1" />
</form>
EOH;

if (isset($s['step']) and $s['step']) $_REQUEST['step'] = $s['step'];
if (isset($_REQUEST['show'])) $_REQUEST['step'] = 'show';

if (isset($_REQUEST['step'])) {

    $exporter = new google_doc_exporter(array(
        'clientId' => $s['clientId'],
        'clientSecret' => $s['clientSecret'],
        'redirectUri' => $thisPage,
    ));
    if ($accessToken) $exporter->setAccessToken($accessToken);

    switch ($_REQUEST['step']) {
        case '2':

            $s['step'] = '3';

            $exporter->authenticate($accessToken);
            $exporter->redirect($thisPage);

            break;

        case '3':

            $list = '<dl>';

            if ( ! isset($s['listOfDocument'])) $s['listOfDocument'] = $exporter->getList();
            $listOfDocument = $s['listOfDocument'];
            foreach ($listOfDocument as $index => $item) {
                if ($item['mimeType'] == 'application/vnd.google-apps.document') {

                    $list .= "<dt>{$item['title']}</dt>";

                    $list .= '<dd>';

                    /*
                    $url = urlencode($item['exportLinks']['text/html']);

                    $list .= "<a class=\"gdoc-item btn btn-mini\" href=\"index.php?ajax=1&format=html&show=1&url={$url}\">AJAX</a>";
                    $list .= ' ';
                    $list .= "<a class=\"btn btn-mini\" target=\"_blank\" href=\"index.php?show=1&format=html&url={$url}\">New Window</a>";
                    $list .= ' ';
                    $list .= "<a class=\"gdoc-item btn btn-mini\" href=\"index.php?show=1&ajax=1&format=raw1&url={$url}\">HTML 1</a>";
                    $list .= ' ';
                    $list .= "<a class=\"gdoc-item btn btn-mini\" href=\"index.php?show=1&ajax=1&format=raw2&url={$url}\">HTML 2</a>";
                    */

                    $id = $item['id'];

                    $list .= "<a class=\"gdoc-item btn btn-mini\" href=\"index.php?ajax=1&format=html&show=1&id={$id}\">AJAX</a>";
                    $list .= ' ';
                    $list .= "<a class=\"btn btn-mini\" target=\"_blank\" href=\"index.php?show=1&format=html&id={$id}\">New Window</a>";
                    $list .= ' ';
                    $list .= "<a class=\"gdoc-item btn btn-mini\" href=\"index.php?show=1&ajax=1&format=raw1&id={$id}\">HTML 1</a>";
                    $list .= ' ';
                    $list .= "<a class=\"gdoc-item btn btn-mini\" href=\"index.php?show=1&ajax=1&format=raw2&id={$id}\">HTML 2</a>";

                    $list .= '</dd>';
                }
            }
            $list .= '</dl>';

            $content['body'] = <<<EOH
<p><a href="index.php?reset=1">This link clears internal cache. Recommended to click this before leaving this site.</a></p>
$list
EOH;

            break;

        case 'show':

            if ( ! isset($_GET['url']) and  ! isset($_GET['id'])) {
                $s['step'] = '1';
                header('Location: ' . $thisPage);
                exit;
            }
            if (isset($_GET['url']) and isset($_GET['id'])) {
                echo 'You cannot specify both URL and ID';
                exit;
            }

            if ( ! isset($s['contents'])) {
                $s['contents'] = array(
                    'id' => array(),
                    'url' => array(),
                    'data' => array()
                );
            }
            if (isset($_GET['url'])) {
                $index = array_search($_GET['url'], $s['contents']['url']);
                if ($index === false) {
                    $content = $exporter->parse($exporter->getContent($_GET['url']));
                    if ($content['body'] == '') {
                        echo 'Document not found by URL';
                        exit;
                    }
                    $s['contents']['id'][] = $data['id'];
                    $s['contents']['url'][] = 'dummy-' . $data['id'];
                    $s['contents']['data'][] = $content;
                    $index = count($s['contents']['url']) - 1;
                }
            }
            if (isset($_GET['id'])) {
                $index = array_search($_GET['id'], $s['contents']['id']);
                if ($index === false) {
                    $data = $exporter->getDocumentById($_GET['id']);
                    if (empty($data['id'])) {
                        echo 'Document not found by ID';
                        exit;
                    }
                    $content = $exporter->getContentById($_GET['id']);
                    $s['contents']['id'][] = $_GET['id'];
                    $s['contents']['url'][] = $data['exportLinks']['text/html'];
                    $s['contents']['data'][] = $content;
                    $index = count($s['contents']['url']) - 1;
                }
            }

            if ( ! isset($_GET['format'])) $_GET['format'] = 'html';
            switch ($_GET['format']) {
                case 'raw1':
                    $content['body'] = <<<EOH
<form id="gdoc-raw-data">
<input class="input-block-level" type="input" value="{$s['contents']['data'][$index]['title']}" />
<textarea class="input-block-level">
{$s['contents']['data'][$index]['body']}
</textarea>
</form>
EOH;
                    break;
                case 'raw2':
                    if ($s['contents']['data'][$index]['title']) {
                        $s['contents']['data'][$index]['title'] = '<h1>' . $s['contents']['data'][$index]['title'] . '</h1>';
                    }
                    $content['body'] = <<<EOH
<form id="gdoc-raw-data">
<textarea class="input-block-level">
{$s['contents']['data'][$index]['title']}{$s['contents']['data'][$index]['body']}
</textarea>
</form>
EOH;
                    break;
                case 'html':
                defaul:
                    if (isset($_GET['ajax'])) {
                        $content['body'] = <<<EOH
<h1 id="gdoc-item-title">{$s['contents']['data'][$index]['title']}</h1>
<div id="gdoc-item-body">{$s['contents']['data'][$index]['body']}</div>
EOH;
                    } else {
                        $content['title'] = $s['contents']['data'][$index]['title'];
                        $content['body'] = <<<EOH
<div id="gdoc-item-body">{$s['contents']['data'][$index]['body']}</div>
EOH;
                    }
            }


            // $content = $exporter->getContentByTitle('Test', array('inline' => true));
            // $content = $exporter->getContentByTitle('Test');

            if (isset($_GET['ajax'])) {
                echo $content['body'];
                exit;
            }

            break;

        default:
        case '1':

            if ( ! $s['clientId'] or ! $s['clientSecret']) {
                $content['body'] = $form_1;
                $message = 'Invalid Client ID or Client secret';
                break;
            }

            $s['step'] = '2';

            $exporter->authenticate($accessToken);
    }
} else {
    session_destroy();
    $content['body'] = $form_1;
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<script>document.cookie='resolution='+Math.max(screen.width,screen.height)+'; path=/';</script>
<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
<link href="application.css" rel="stylesheet">
<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript">

  var _gaq = _gaq || [];
  var pluginUrl = '//www.google-analytics.com/plugins/ga/inpage_linkid.js';
  _gaq.push(['_require', 'inpage_linkid', pluginUrl]);
  _gaq.push(['_setAccount', 'UA-39856458-5']);
  _gaq.push(['_setDomainName', 'hironozu.com']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'stats.g.doubleclick.net/dc.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</head>
<body>
<div class="container-fluid">
    <header><h1><?php echo $content['title']; ?></h1></header>
<?php
    if ($message) {
?>
    <div id="message" class="alert">
        <a href="#" class="close" data-dismiss="alert">&times;</a>
        <?php echo $message; ?>
    </div>
<?php
    }
?>
    <div id="content" class="row-fluid">
        <?php echo $content['body']; ?>
    </div>
    <div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h1 id="myModalLabel">Google Doc Content</h1>
      </div>
      <div class="modal-body"></div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
      </div>
    </div>
    <footer>
        <a target="_github" href="https://github.com/hironozu/google_doc_exporter_demo">See source at GitHub</a><br />
        Copyright © 2013 Hiro Nozu (<a title="Web Developer Brisbane" href="http://hironozu.com/">http://hironozu.com</a>)
    </footer>
</div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="application.js"></script>
</body>
</html>
