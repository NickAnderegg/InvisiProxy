<?php

require 'Invisi.class.php';

$config = array
(
    'url_var_name'             => 'q',
    'flags_var_name'           => 'hl',
    'get_form_name'            => '__script_get_form',
    'proxy_url_form_name'      => 'poxy_url_form',
    'proxy_settings_form_name' => 'poxy_settings_form',
    'max_file_size'            => -1
);

$flags = 'prev';

if (isset($_GET[$config['flags_var_name']]))
{
    $flags = $_GET[$config['flags_var_name']];
	//echo $config['flags_var_name']."\n";
	//echo $_GET[$config['flags_var_name']];
}

$Invisi = & new Invisi($config, $flags);

//echo (isset($_GET[$Invisi->config['get_form_name']]) ? "Set!" : "Not set!");

if (isset($_GET[$Invisi->config['get_form_name']]))
{
    $url = decode_url($_GET[$Invisi->config['get_form_name']]);
	//echo "URL Var: ".$url;
    $qstr = preg_match('#\?#', $url) ? (strpos($url, '?') === strlen($url) ? '' : '&') : '?';
	//echo "qstr: ".$qstr;
    $arr = explode('&', $_SERVER['QUERY_STRING']);
    if (preg_match('#^'.$Invisi->config['get_form_name'].'#', $arr[0]))
    {
        array_shift($arr);
    }
    $url .= $qstr . implode('&', $arr);
    $Invisi->start_transfer(encode_url($url));
    echo $Invisi->return_response();
    exit();
}

if (isset($_GET[$Invisi->config['url_var_name']]))
{
	/*echo "Entered isset url_var_name\n";
	echo $Invisi->config['url_var_name']."\n";
	echo $_GET[$Invisi->config['url_var_name']];*/
    $Invisi->start_transfer($_GET[$Invisi->config['url_var_name']]);
    echo $Invisi->return_response();
    exit();
}

if (isset($_GET['action'], $_GET['delete']) && $_GET['action'] == 'cookies')
{
    $Invisi->delete_cookies($_GET['delete']);
    header("Location: $Invisi->script_url?action=cookies");
    exit();
}

if (isset($_POST['username'], $_POST['password'], $_POST['server'], $_POST['realm'], $_POST['auth_url']))
{
    $Invisi->request_method = 'GET';
    $Invisi->url_segments['host'] = decode_url($_POST['server']);
    $Invisi->set_authorization($_POST['username'], $_POST['password']);
    $Invisi->start_transfer($_POST['auth_url']);
    echo $Invisi->return_response();
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>InvisiProxy</title>
  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
  <link rel="stylesheet" type="text/css" href="style.css" media="all" />
  <script src="javascript.js" type="text/javascript"></script>
</head>
<body>
<div id="container">
  <div id="menu">
    <a href="<?php echo $_SERVER['PHP_SELF'] ?>">URL Form</a> | 
    <a href="?action=cookies">Manage Cookies</a>
  </div>
  <div class="title">InvisiProxy</div>
  <noscript><div class="error"><big>You have Javascript disabled. Please enable it to use the proxy</big></div></noscript>
<?php

if (isset($_GET['error']))
{
    echo '<div class="error"><b>Error:</b> ' . htmlspecialchars($_GET['error']) . '</div>';
    if (isset($_GET['retry']))
    {
        echo '<div class="error"><a href="'. $Invisi->proxify_url(decode_url($_GET['retry'])) .'">Retry</a></div>';
    } 
}

if (isset($_GET['action']))
{
    if ($_GET['action'] == 'cookies')
    {
        $cookies = $Invisi->get_cookies('COOKIE', false);

        if (!empty($cookies))
        {
            echo '<table style="width: 100%">';
            echo '<tr><td class="option" colspan="5"><a href="?action=cookies&delete=all">Clear All Cookies</a></td></tr>';
            echo '<tr><td class="head">Name</td><td class="head">Domain</td><td class="head">Path</td><td class="head">Value</td><td class="head">Action</td></tr>';

            for ($i = 0; $i < count($cookies); $i++)
            {
                $j = $i&1 ? ' class="shade"' : '';
                echo "<tr><td$j>{$cookies[$i][0]}</td><td$j>{$cookies[$i][1]}</td><td$j>{$cookies[$i][2]}</td>"
                   . "<td$j>" . wordwrap($cookies[$i][3], 15, ' ') ."</td><td$j><a href=". '"?action=cookies&delete='. md5(implode('', $cookies[$i])) . '">delete</a></td></tr>';
            }

            echo '</table>';
        }
        else
        {
            echo '<div class="error">No cookies available.</div>';
        }
    }
    else if ($_GET['action'] == 'auth' && isset($_GET['server'], $_GET['realm'], $_GET['auth_url']))
    {
        echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
        echo '<input type="hidden" name="server" value="'. $_GET['server'] .'" />';
        echo '<input type="hidden" name="realm" value="'. $_GET['realm'] .'" />';
        echo '<input type="hidden" name="auth_url" value="'. $_GET['auth_url'] .'" />';
        echo '<table style="width: 100%">';
        echo '<tr><td colspan="2" class="option">Enter user name and password for <b>' . decode_url($_GET['realm']) . '</b> at <i>' . decode_url($_GET['server']) . '</i></td></tr>';
        echo '<tr><td width="30%" class="option">User name</td><td class="option"><input type="text" name="username" value="" /></td></tr>';
        echo '<tr><td width="30%" class="option">Password</td><td class="option"><input type="password" name="password" value="" /></td></tr>';
        echo '<tr><td colspan="2" style="text-align: center"><input type="submit" value="OK" /></td></tr>';
        echo '</table>';
        echo '</form>';
    }
} 
else
{
  ?>
  <form name="<?php echo $Invisi->config['proxy_url_form_name'] ?>" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
  <input type="hidden" name="<?php echo $Invisi->config['url_var_name'] ?>" value="" id="url_input" />
  <input type="hidden" name="<?php echo $Invisi->config['flags_var_name'] ?>" value="" />
  </form>
  
  <form name="<?php echo $Invisi->config['proxy_settings_form_name'] ?>" method="get" action="" onsubmit="return submit_form();">
  <table style="width: 100%">
  <tr><td class="option" style="width: 20%">URL</td><td class="option" style="width: 80%">&nbsp;<input type="text" name="url" size="70" value="" /></td></tr>
  <?php echo $Invisi->options_list(true, true) ?>
  <!--Never allow new window. Function disabled in InvisiProxy. <tr><td class="option" style="width: 20%">New Window</td><td class="option" style="width: 80%">--><input type="hidden" name="new_window" /><!--Open URL in a new window </td></tr>-->
  </table>
  <div style="text-align: center"><input type="submit" name="browse" value="Browse" onclick="return submit_form();" /></div>
  <div style="text-align: center">InvisiProxy <?php echo $Invisi->version ?> &copy; <?php echo (date("Y") == "2011" ? "2011" : "2011-".date("Y")) ?> <a href="http://nickanderegg.me">Nick Anderegg</a>
</div>
  </form>
  <?php
}

echo '</div></body></html>';
?>