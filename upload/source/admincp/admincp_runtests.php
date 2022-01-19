<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

if(!defined('DISCUZ_DEBUG') || !DISCUZ_DEBUG) {
	cpheader();
	cpmsg('action_noaccess', '', 'error');
}

if(!isfounder()) cpmsg('noaccess_isfounder', '', 'error');

$test_main_file = __DIR__ . "/../../tests/runtests.php";

if (!file_exists($test_main_file)) {
        exit('No tests found');
}

$dbfile = __DIR__ . "/progress.db";

if ($operation === "prepare") {
        unlink($dbfile);
        ob_start();
        echo "ok";
        ob_end_flush();
        exit();
}

if ($operation === "start") {
        $sl3 = new SQLite3(__DIR__ . "/progress.db");
        // id 会自增
        $sl3->exec('create table tests (id INTEGER PRIMARY KEY, message TEXT NOT NULL)');

        define("IN_UNITTESTING", 1);
        require_once($test_main_file);
        global $LOGGING_OUTPUT_FUNC;
        $LOGGING_OUTPUT_FUNC = function($str) use (&$sl3) {
                $stmt = $sl3->prepare("insert into tests (message) values (:message)");
                $stmt->bindParam(":message", trim($str), SQLITE3_TEXT);
                $stmt->execute();
                $stmt->close();
        };
        runtests_main();
        $sl3->close();
        exit();
}

if ($operation === "fetch") {
        $sl3 = new SQLite3(__DIR__ . "/progress.db", SQLITE3_OPEN_READONLY);
        $stmt = $sl3->prepare("select id, message from tests where id > :from ORDER BY id");
	    $stmt->bindParam(":from", dintval($_GET['from']), SQLITE3_INTEGER);
	    $rs = $stmt->execute();
        $rsa = array();
        while($row = $rs->fetchArray(SQLITE3_ASSOC)) {
                $rsa[] = $row;
        }
        $stmt->close();
        $sl3->close();
        header('Content-Type: application/json');
        ob_start();
        echo json_encode($rsa);
        ob_end_flush();
        exit();
}

// 以下为没有任何operation时显示的默认页面

cpheader();

shownav('global', 'setting_runtests');
showsubmenu('setting_runtests');

?>
<?=cplang('setting_runtests_prompt')?> &nbsp;
<input type="submit" class="btn" name="confirmed" value="<?=cplang('ok')?>">
<div>
        <pre id="content"></pre>
</div>

<style>

#content { 
        margin: 20px; 
        padding: 5px; 
        text-align: left; 
        line-height: 1.3em;
        font-family: "Fira Code",Menlo,Consolas,monospace;
}

.failed {
        color: red;
}
</style>

<script type="text/JavaScript">

var ajax = {};
ajax.x = function () {
    if (typeof XMLHttpRequest !== 'undefined') {
        return new XMLHttpRequest();
    }
    var versions = [
        "MSXML2.XmlHttp.6.0",
        "MSXML2.XmlHttp.5.0",
        "MSXML2.XmlHttp.4.0",
        "MSXML2.XmlHttp.3.0",
        "MSXML2.XmlHttp.2.0",
        "Microsoft.XmlHttp"
    ];

    var xhr;
    for (var i = 0; i < versions.length; i++) {
        try {
            xhr = new ActiveXObject(versions[i]);
            break;
        } catch (e) {
        }
    }
    return xhr;
};

ajax.send = function (url, callback, method, data, async) {
    if (async === undefined) {
        async = true;
    }
    var x = ajax.x();
    x.open(method, url, async);
    x.onreadystatechange = function () {
        if (x.readyState == 4) {
            callback(x.responseText)
        }
    };
    if (method == 'POST') {
        x.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    }
    x.send(data)
};

ajax.get = function (url, data, callback, async) {
    var query = [];
    for (var key in data) {
        query.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
    }
    ajax.send(url + (query.length ? '?' + query.join('&') : ''), callback, 'GET', null, async)
};

var MAX_ID = 0;

function read_status() {
        ajax.get('<?php echo ADMINSCRIPT; ?>?action=runtests&operation=fetch&from=' + MAX_ID, '', function(s) {
                try {
                        var items = JSON.parse(s);
                        for (i = 0; i < items.length; ++i) {
                                if (items[i].message.indexOf('FAILED') !== -1) items[i].message = '<span class="failed">' + items[i].message + '</span>';
                                top.frames['main'].document.getElementById('content').innerHTML += items[i].message + '<br />';
                                top.frames['main'].scrollTo(0,top.frames['main'].document.body.scrollHeight);
                                MAX_ID = items[i].id;
                                if (items[i].message.indexOf('totally') !== -1) return;
                        }
                } catch (e) {
                }
                read_status();
        })
}

function prepare() {
        ajax.get('<?php echo ADMINSCRIPT; ?>?action=runtests&operation=prepare', "", function(s) {
                top.frames['main'].document.getElementById('content').innerHTML += "previous log deleted <br/> <br/>";
                MAX_ID = 0;
                ajax.get('<?php echo ADMINSCRIPT; ?>?action=runtests&operation=start');
                read_status();
        });
}

var btn = top.frames['main'].document.getElementsByName('confirmed');
btn[0].onclick = function() {
        top.frames['main'].document.getElementById('content').innerHTML = "test started <br/>";
        prepare();
        return false;
}
</script>
